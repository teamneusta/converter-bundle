<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
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
                ->arrayNode('context')
                    ->info('Mapping of context objects/properties (value) to target properties (key)')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('target')
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifNull()
                            ->then(static fn () => ['property' => null])
                        ->end()
                        ->beforeNormalization()
                            ->ifString()
                            // A namespace separator is required to opt into the "class" shortcut, so that a plain
                            // property name (e.g. "locale") is never misread as an unrelated, unnamespaced class
                            // that happens to be loaded (e.g. ext-intl's global `Locale` class - PHP class name
                            // lookups are case-insensitive). A class-string shortcut that doesn't resolve to an
                            // existing class still fails loudly via the "class" node's validation below.
                            ->then(static fn (string $v) => str_contains($v, '\\') ? ['class' => $v] : ['property' => $v])
                        ->end()
                        ->children()
                            ->scalarNode('class')
                                ->info('Class of the context object to read "property" from')
                                ->validate()
                                    ->ifTrue(static fn ($v) => null !== $v && !class_exists($v))
                                    ->thenInvalid('The context object class %s does not exist.')
                                ->end()
                            ->end()
                            ->scalarNode('property')->end()
                            ->booleanNode('required')
                                ->info('Whether to fail instead of silently skipping the mapping if the context value/object is missing')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
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

        $propertiesNodeBuilder = $node
            ->children()
                ->arrayNode('properties')
                    ->info('Mapping of source properties (value) to target properties (key)')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('target')
                    ->arrayPrototype()
        ;

        $factories->getDefaultPopulatorFactory()->addConfiguration($propertiesNodeBuilder);

        $propertyTypes = array_keys($factories->getPropertyPopulatorFactories());

        foreach ($propertyTypes as $type) {
            $factories->getPropertyPopulatorFactories()[$type]
                ->addPropertyConfiguration($propertiesNodeBuilder->children()->arrayNode($type));
        }

        // Without an explicit type key a property falls back to the default populator type, so at
        // most one type key may be present - otherwise one of them would silently be ignored.
        $propertiesNodeBuilder
            ->validate()
                ->ifTrue(static fn (array $c) => \count(array_intersect(array_keys($c), $propertyTypes)) > 1)
                ->thenInvalid('You cannot set multiple populator types for the same property.')
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
            $populatorFactory = $factories->getPropertyPopulatorFactoryFor($sourceConfig);
            $typeConfig = $sourceConfig[$populatorFactory->getType()] ?? [];
            $sourceConfig = array_diff_key($sourceConfig, $factories->getPropertyPopulatorFactories());

            $populatorFactory->create(
                $container,
                $propertyPopulatorId,
                ['target' => $targetProperty] + $sourceConfig + $typeConfig,
            );
        }

        foreach ($config['context'] ?? [] as $targetProperty => $contextConfig) {
            $contextClass = $contextConfig['class'] ?? null;
            $contextProperty = $contextConfig['property']
                ?? (null !== $contextClass ? self::inferContextProperty($contextClass) : null)
                ?? $targetProperty;

            $config['populators'][] = $contextPopulatorId = "{$id}.populator.context.{$targetProperty}";
            $container->register($contextPopulatorId, ContextMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$contextClass' => $contextClass,
                    '$contextProperty' => $contextProperty,
                    '$mapper' => null,
                    '$accessor' => new Reference('property_accessor'),
                    '$required' => $contextConfig['required'] ?? false,
                ]);
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

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }

    /**
     * @param class-string $class
     */
    private static function inferContextProperty(string $class): ?string
    {
        $properties = array_values(array_filter(
            (new \ReflectionClass($class))->getProperties(),
            static fn (\ReflectionProperty $property) => !$property->isStatic(),
        ));

        return 1 === \count($properties) ? $properties[0]->getName() : null;
    }
}
