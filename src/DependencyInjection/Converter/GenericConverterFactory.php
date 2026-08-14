<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class GenericConverterFactory implements ConverterFactory
{
    public function getType(): string
    {
        return 'generic';
    }

    public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
    {
        $node
            ->fixXmlConfig('populator')
            ->fixXmlConfig('property', 'properties')
            ->children()
                ->scalarNode('target_factory')
                    ->info('Service id of the TargetFactory')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('populators')
                    ->info('Service ids of the Populator\'s')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('context')
                    ->info('Mapping of context properties (value) to target properties (key)')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('target')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(fn (array $c) => empty($c['populators']) && empty($c['properties']) && empty($c['context']))
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

        $factories->getPropertyMappingPopulatorFactory()->addConfiguration($propertiesNodeBuilder);

        foreach ($factories->getPropertyPopulatorFactories() as $type => $populatorFactory) {
            $populatorFactory->addPropertyConfiguration($propertiesNodeBuilder->children()->arrayNode($type));
        }
    }

    public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
    {
        foreach ($config['properties'] ?? [] as $targetProperty => $sourceConfig) {
            // Todo: This leaks the `?`: should we return the ID instead? Or make it a reference?
            $config['populators'][] = $propertyPopulatorId = rtrim("{$id}.populator.{$targetProperty}", '?');
            $factories->getFirstMatchingPopulatorFactory(array_keys($sourceConfig))
                ->create($container, $propertyPopulatorId, ['target' => $targetProperty] + $sourceConfig);
        }

        foreach ($config['context'] ?? [] as $targetProperty => $contextProperty) {
            $config['populators'][] = $contextPopulatorId = "{$id}.populator.context.{$targetProperty}";
            $container->register($contextPopulatorId, ContextMappingPopulator::class)
                ->setArguments([
                    '$targetProperty' => $targetProperty,
                    '$contextProperty' => $contextProperty ?? $targetProperty,
                    '$mapper' => null,
                    '$accessor' => new Reference('property_accessor'),
                ]);
        }

        $container->registerAliasForArgument($id, Converter::class, $this->ensureSuffix($id, 'Converter'));
        $container->register($id, GenericConverter::class)
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($config['target_factory']),
                '$populators' => array_map(
                    fn (string $populator) => new Reference($populator),
                    $config['populators'],
                ),
            ]);
    }

    private function ensureSuffix(string $value, string $suffix): string
    {
        return str_ends_with($value, $suffix) ? $value : $value . $suffix;
    }
}
