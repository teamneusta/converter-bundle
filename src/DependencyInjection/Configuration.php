<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neusta_converter');
        $rootNode = $treeBuilder->getRootNode();

        $this->addConverterSection($rootNode);
        $this->addPopulatorSection($rootNode);

        return $treeBuilder;
    }

    private function addConverterSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('converter')
                    ->info('Converter configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->fixXmlConfig('populator')
                        ->fixXmlConfig('property', 'properties')
                        ->children()
                            ->scalarNode('converter')
                                ->info('Class name of the "Converter" implementation')
                                ->defaultValue(GenericConverter::class)
                            ->end()
                            ->scalarNode('target_factory')
                                ->info('Service id of the "TargetFactory"')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('populators')
                                ->info('Service ids of the "Populator"s')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('properties')
                                ->info('Mapping of source properties (value) to target properties (key)')
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('target')
                                ->arrayPrototype()
                                    ->beforeNormalization()
                                        ->ifNull()
                                        ->then(fn () => ['source' => null, 'default' => null, 'nullsafe' => false])
                                    ->end()
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(fn (string $v) => ['source' => $v, 'default' => null, 'nullsafe' => false])
                                    ->end()
                                    ->children()
                                        ->scalarNode('source')
                                            ->defaultValue(null)
                                        ->end()
                                        ->scalarNode('default')
                                            ->defaultValue(null)
                                        ->end()
                                        ->scalarNode('nullsafe')
                                            ->defaultValue(false)
                                        ->end()
                                    ->end()
                                ->end()
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
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPopulatorSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('populator')
                    ->info('Populator configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('populator')
                                ->info('class of the "Populator" implementation')
                                ->values([ConvertingPopulator::class, ArrayConvertingPopulator::class])
                                ->defaultValue(ConvertingPopulator::class)
                            ->end()
                            ->scalarNode('converter')
                                ->info('Service id of the internal "Converter"')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('property')
                                ->info('Mapping of source property to target property')
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('target')
                                ->arrayPrototype()
                                    ->beforeNormalization()
                                        ->ifNull()
                                        ->then(fn () => ['source' => null, 'source_array_item' => null])
                                    ->end()
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(fn (string $v) => ['source' => $v, 'source_array_item' => null])
                                    ->end()
                                    ->children()
                                        ->scalarNode('source')
                                            ->defaultValue(null)
                                        ->end()
                                        ->scalarNode('source_array_item')
                                            ->defaultValue(null)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(fn (array $c) => ArrayConvertingPopulator::class !== $c['populator'] && !empty($c['property'][array_key_first($c['property'])]['source_array_item']))
                            ->thenInvalid('The "property.<target>.source_array_item" option is only supported for array converting populators.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
