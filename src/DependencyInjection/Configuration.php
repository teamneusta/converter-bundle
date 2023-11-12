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
                                        ->then(fn () => ['source' => null, 'default' => null])
                                    ->end()
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(fn (string $v) => ['source' => $v, 'default' => null])
                                    ->end()
                                    ->children()
                                        ->scalarNode('source')
                                            ->defaultValue(null)
                                        ->end()
                                        ->scalarNode('default')
                                            ->defaultValue(null)
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
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(function (array $c) {
                                if (ConvertingPopulator::class !== $c['populator']) {
                                    return false;
                                }

                                if (1 !== count($c['property'])) {
                                    return true;
                                }

                                $value = $c['property'][array_key_first($c['property'])];

                                return null !== $value && !is_string($value);
                            })
                            ->thenInvalid('Converting populators must contain a mapping with one "<target>: ~" or "<target>: <source>" entry as "property".')
                        ->end()
                        ->validate()
                            ->ifTrue(function (array $c) {
                                if (ArrayConvertingPopulator::class !== $c['populator']) {
                                    return false;
                                }

                                if (1 !== count($c['property'])) {
                                    return true;
                                }

                                $value = $c['property'][array_key_first($c['property'])];

                                if (null === $value || is_string($value)) {
                                    return false;
                                }

                                if (!is_array($value) || [] === $value || 2 < count($value)) {
                                    return true;
                                }

                                if (2 === count($value) && !array_key_exists('source', $value)) {
                                    return true;
                                }

                                return empty($value['source_array_item']);
                            })
                            ->thenInvalid('Array converting populators must contain a mapping with one "<target>: ~", "<target>: <source>", "<target>: { source_array_item: <array item key> }", or "<target>: { source: <source>, source_array_item: <array item key> }" entry as "property".')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
