<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\DefaultConverter;
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
                                ->defaultValue(DefaultConverter::class)
                            ->end()
                            ->scalarNode('target_factory')
                                ->info('Service id of the "TargetTypeFactory"')
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
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(fn (array $c) => empty($c['populators']) && empty($c['properties']))
                            ->thenInvalid('At least one "populator" or "property" must be defined.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
