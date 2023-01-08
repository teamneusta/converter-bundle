<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\DefaultConverter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
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
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->beforeNormalization()->castToArray()->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('cached')
                                ->info('Whether the result should be cached')
                                ->children()
                                    ->scalarNode('service')
                                        ->info('Service id to override the cache management entirely')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('key_factory')
                                        ->info('Service id of the "CacheKeyFactory"')
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(fn (array $c) => isset($c['service'], $c['key_factory']))
                                    ->thenInvalid('You cannot use "service" and "key_factory" at the same time.')
                                ->end()
                                ->validate()
                                    ->ifTrue(fn (array $c) => !isset($c['service']) && !isset($c['key_factory']))
                                    ->thenInvalid('Either "service" or "key_factory" must be defined.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
