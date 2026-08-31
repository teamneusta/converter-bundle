<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\Populator\ContextMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;
use Neusta\ConverterBundle\Target\GenericTargetWithPropertiesFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class GenericConverterFactory implements ConverterFactory
{
    public const TYPE = 'generic';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
    {
        $node
            ->fixXmlConfig('populator')
            ->fixXmlConfig('property', 'properties')
            ->children()
                ->arrayNode('target')
                    ->info('Target configuration')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn ($v) => ['class' => $v])
                    ->end()
                    ->children()
                        ->scalarNode('class')
                            ->info('Class name of the target')
                            ->validate()
                                ->ifTrue(static fn ($v) => !class_exists($v))
                                ->thenInvalid('The target type %s does not exist.')
                            ->end()
                        ->end()
                        ->arrayNode('properties')
                            ->info('Properties of the target')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('name')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('target_factory')
                    ->info('Service id of the TargetFactory')
                ->end()
                ->arrayNode('populators')
                    ->info('Service ids of the Populator\'s')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static fn (array $c) => !isset($c['target']) && !isset($c['target_factory']))
                ->thenInvalid('Either "target" or "target_factory" must be defined.')
            ->end()
            ->validate()
                ->ifTrue(static fn (array $c) => isset($c['target'], $c['target_factory']))
                ->thenInvalid('Either "target" or "target_factory" must be defined, but not both.')
            ->end()
            ->validate()
                ->ifTrue(static fn (array $c) => empty($c['populators']) && empty($c['properties']) && empty($c['context']))
                ->thenInvalid('At least one "populator", "property" or "context" must be defined.')
            ->end()
        ;

        $contextNodeBuilder = $node
            ->children()
                ->arrayNode('context')
                    ->info('Mapping of context objects/properties (value) to target properties (key)')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('target')
                    ->arrayPrototype()
        ;

        $this->getContextMappingPopulatorFactory($factories)->addConfiguration($contextNodeBuilder);

        $propertiesNodeBuilder = $node
            ->children()
                ->arrayNode('properties')
                    ->info('Mapping of source properties (value) to target properties (key)')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('target')
                    // "?" is just a skip_null marker, so "foo" and "foo?" are the same property.
                    ->validate()
                        ->ifTrue(static function (array $c): bool {
                            $targets = array_map(
                                static fn (string $k) => str_ends_with($k, '?') ? substr($k, 0, -1) : $k,
                                array_keys($c),
                            );

                            return \count($targets) !== \count(array_unique($targets));
                        })
                        ->thenInvalid('A target property cannot be configured both with and without the "?" (skip_null) suffix.')
                    ->end()
                    ->arrayPrototype()
        ;

        $allPropertyTypeFactories = $this->getAllPropertyTypeFactories($factories);
        $propertyTypes = array_keys($allPropertyTypeFactories);

        // Every type - including the default one - gets its own node, like a standalone
        // `populators.<id>.<type>` entry (see `Configuration::addPopulatorSection()`).
        foreach ($allPropertyTypeFactories as $type => $factory) {
            $typeNode = $propertiesNodeBuilder->children()->arrayNode($type);
            $factory->addConfiguration($typeNode);
            if ($factory instanceof PropertyPopulatorFactory) {
                $factory->addPropertyConfiguration($typeNode);
            }
        }

        // A bare scalar/null value, or a map without an explicit type key, is the shorthand for the
        // default type - the same shorthand `properties: { name: sourceProp }` supported before every
        // type had its own key.
        $propertiesNodeBuilder
            ->beforeNormalization()
                ->ifTrue(static fn ($v) => null === $v || \is_string($v) || (\is_array($v) && !array_intersect(array_keys($v), $propertyTypes)))
                ->then(static fn ($v) => [PropertyMappingPopulatorFactory::TYPE => $v])
            ->end()
        ;

        $propertiesNodeBuilder
            ->validate()
                ->ifTrue(static fn (array $c) => 1 !== \count(array_intersect(array_keys($c), $propertyTypes)))
                ->thenInvalid('Exactly one populator type must be set for a property.')
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
    {
        $targetFactoryId = $config['target_factory'] ?? "{$id}.target_factory";
        if (!isset($config['target_factory'])) {
            $container->register($targetFactoryId, GenericTargetWithPropertiesFactory::class)
                ->setArguments([
                    '$type' => $config['target']['class'],
                    '$properties' => $config['target']['properties'] ?? [],
                ]);
        }

        $allPropertyTypeFactories = $this->getAllPropertyTypeFactories($factories);

        foreach ($config['properties'] ?? [] as $targetProperty => $propertyConfig) {
            // A trailing "?" on the target property is the shorthand for "skip_null: true". It only
            // exists here because the target property is a YAML key and cannot carry options.
            $skipNull = str_ends_with($targetProperty, '?');
            if ($skipNull) {
                $targetProperty = substr($targetProperty, 0, -1);
            }

            $config['populators'][] = $propertyPopulatorId = "{$id}.populator.{$targetProperty}";

            // Normalization (see addConfiguration()) guarantees exactly one type key is present.
            $type = (string) array_key_first(array_intersect_key($propertyConfig, $allPropertyTypeFactories));
            $typeConfig = $propertyConfig[$type];
            if ($skipNull) {
                $typeConfig['skip_null'] = true;
            }

            $allPropertyTypeFactories[$type]->create($container, $propertyPopulatorId, ['target' => $targetProperty] + $typeConfig);
        }

        if ($config['context'] ?? []) {
            $contextMappingPopulatorFactory = $this->getContextMappingPopulatorFactory($factories);

            foreach ($config['context'] as $targetProperty => $contextConfig) {
                $config['populators'][] = $contextPopulatorId = "{$id}.populator.context.{$targetProperty}";

                $contextMappingPopulatorFactory->create(
                    $container,
                    $contextPopulatorId,
                    ['target' => $targetProperty] + $contextConfig,
                );
            }
        }

        $container->registerAliasForArgument($id, Converter::class, $this->ensureSuffix($id, 'Converter'));
        $container->register($id, GenericConverter::class)
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($targetFactoryId),
                '$populators' => array_map(
                    static fn (string $populator) => new Reference($populator),
                    $config['populators'],
                ),
            ]);
    }

    /**
     * The `context` key above is built on top of this type, so it must always be registered.
     */
    private function getContextMappingPopulatorFactory(FactoryRegistry $factories): ContextMappingPopulatorFactory
    {
        $factory = $factories->getPopulatorFactory(ContextMappingPopulatorFactory::TYPE);

        if (!$factory instanceof ContextMappingPopulatorFactory) {
            throw new \LogicException(\sprintf(
                'The mandatory populator factory for the type "%s" is not registered. Expected an instance of "%s", got "%s".',
                ContextMappingPopulatorFactory::TYPE,
                ContextMappingPopulatorFactory::class,
                get_debug_type($factory),
            ));
        }

        return $factory;
    }

    /**
     * The default populator type is what a property mapping without an explicit type key resolves
     * to, e.g. `properties: { fullName: name }`.
     */
    private function getDefaultPopulatorFactory(FactoryRegistry $factories): PropertyMappingPopulatorFactory
    {
        $factory = $factories->getPopulatorFactory(PropertyMappingPopulatorFactory::TYPE);

        if (!$factory instanceof PropertyMappingPopulatorFactory) {
            throw new \LogicException(\sprintf(
                'The mandatory populator factory for the default type "%s" is not registered. Expected an instance of "%s", got "%s".',
                PropertyMappingPopulatorFactory::TYPE,
                PropertyMappingPopulatorFactory::class,
                get_debug_type($factory),
            ));
        }

        return $factory;
    }

    /**
     * All populator types usable inside `properties:`, keyed by type name - the default type plus
     * every type implementing `PropertyPopulatorFactory`. `addConfiguration()`'s normalization
     * guarantees a property's config always has exactly one of these type names as its only key.
     *
     * @return array<string, PopulatorFactory>
     */
    private function getAllPropertyTypeFactories(FactoryRegistry $factories): array
    {
        $propertyPopulatorFactories = array_filter($factories->getPopulatorFactories(), static fn ($factory) => $factory instanceof PropertyPopulatorFactory);

        return [PropertyMappingPopulatorFactory::TYPE => $this->getDefaultPopulatorFactory($factories)] + $propertyPopulatorFactories;
    }

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
