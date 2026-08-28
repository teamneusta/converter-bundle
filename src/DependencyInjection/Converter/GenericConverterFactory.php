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

        $defaultPopulatorFactory = $this->getDefaultPopulatorFactory($factories);
        $defaultPopulatorFactory->addConfiguration($propertiesNodeBuilder);

        $propertyPopulatorFactories = $this->getPropertyPopulatorFactories($factories);
        $propertyTypes = array_keys($propertyPopulatorFactories);

        foreach ($propertyPopulatorFactories as $type => $propertyPopulatorFactory) {
            $propertyPopulatorFactory->addPropertyConfiguration($propertiesNodeBuilder->children()->arrayNode($type));
        }

        // Without an explicit type key a property falls back to the default populator type, so at
        // most one type key may be present - otherwise one of them would silently be ignored.
        $propertiesNodeBuilder
            ->validate()
                ->ifTrue(static fn (array $c) => \count(array_intersect(array_keys($c), $propertyTypes)) > 1)
                ->thenInvalid('You cannot set multiple populator types for the same property.')
            ->end()
        ;

        // The "default" field is shared/top-level (see above), but whether it's actually supported
        // depends on the selected type - e.g. array types discard it silently, so they reject it.
        $propertiesNodeBuilder
            ->validate()
                ->ifTrue(static function (array $c) use ($propertyPopulatorFactories, $propertyTypes, $defaultPopulatorFactory): bool {
                    $type = array_values(array_intersect(array_keys($c), $propertyTypes))[0] ?? null;
                    $factory = null !== $type ? $propertyPopulatorFactories[(string) $type] : $defaultPopulatorFactory;

                    return !$factory->supportsDefaultValue() && null !== ($c['default'] ?? null);
                })
                ->thenInvalid('The "default" option is not supported for this populator type.')
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

        $propertyPopulatorFactories = $this->getPropertyPopulatorFactories($factories);

        foreach ($config['properties'] ?? [] as $targetProperty => $sourceConfig) {
            // A trailing "?" on the target property is the shorthand for "skip_null: true". It only
            // exists here because the target property is a YAML key and cannot carry options.
            if (str_ends_with($targetProperty, '?')) {
                $targetProperty = substr($targetProperty, 0, -1);
                $sourceConfig['skip_null'] = true;
            }

            $config['populators'][] = $propertyPopulatorId = "{$id}.populator.{$targetProperty}";

            // Inside `properties` the populator type is a nested key, while a populator factory
            // always receives a flat config. Flatten it so both entry points share one contract.
            $populatorFactory = $this->getPropertyPopulatorFactoryFor($factories, $propertyPopulatorFactories, $sourceConfig);
            $typeConfig = $sourceConfig[$populatorFactory->getType()] ?? [];
            $sourceConfig = array_diff_key($sourceConfig, $propertyPopulatorFactories);

            $populatorFactory->create(
                $container,
                $propertyPopulatorId,
                ['target' => $targetProperty] + $sourceConfig + $typeConfig,
            );
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
     * Resolves the populator factory for a single property mapping. The type is expressed as a
     * nested key (e.g. `converting`); without one the default type applies.
     *
     * @param array<string, PropertyPopulatorFactory> $propertyPopulatorFactories
     * @param array<string, mixed>                    $propertyConfig
     */
    private function getPropertyPopulatorFactoryFor(FactoryRegistry $factories, array $propertyPopulatorFactories, array $propertyConfig): PopulatorFactory
    {
        $types = array_keys(array_intersect_key($propertyConfig, $propertyPopulatorFactories));

        if (\count($types) > 1) {
            throw new \LogicException(\sprintf(
                'Only one populator type can be set per property, got "%s".',
                implode('", "', $types),
            ));
        }

        return $factories->getPopulatorFactory($types[0] ?? '') ?? $this->getDefaultPopulatorFactory($factories);
    }

    /**
     * @return array<string, PropertyPopulatorFactory>
     */
    private function getPropertyPopulatorFactories(FactoryRegistry $factories): array
    {
        return array_filter($factories->getPopulatorFactories(), static fn ($factory) => $factory instanceof PropertyPopulatorFactory);
    }

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
