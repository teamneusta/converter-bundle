<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** @var list<ConverterFactory> */
    private array $converterFactories;

    /**
     * @param list<ConverterFactory> $converterFactories
     */
    public function __construct(array $converterFactories)
    {
        $this->converterFactories = $converterFactories;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('neusta_converter');
        $rootNode = $treeBuilder->getRootNode();

        $this->addConverterSection($rootNode);
        $this->addDeprecatedConverterSection($rootNode);

        return $treeBuilder;
    }

    private function addConverterSection(ArrayNodeDefinition $rootNode): void
    {
        $converterNodeBuilder = $rootNode
            //->fixXmlConfig('converter') // Todo: only possible once deprecated config got removed
            ->children()
                ->arrayNode('converters')
                    ->info('Converter configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
        ;

        foreach ($this->converterFactories as $factory) {
            $factory->addConfiguration($converterNodeBuilder->children()->arrayNode($factory->getKey()));
        }

        $converterNodeBuilder
            ->validate()
                ->ifTrue(fn ($v) => \count($v) > 1)
                ->thenInvalid('You cannot set multiple converter types for the same converter.')
            ->end()
            ->validate()
                ->ifEmpty()
                ->thenInvalid('You must set a converter definition for the converter.')
            ->end()
        ;
    }

    private function addDeprecatedConverterSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('converter')
                    ->setDeprecated('teamneusta/converter-bundle', '1.0', 'Please use "neusta_converter.converters" instead.')
                    ->info('Converter configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->fixXmlConfig('populator')
                        ->fixXmlConfig('property', 'properties')
                        ->children()
                            ->scalarNode('converter')
                                ->info('Class name of the Converter implementation')
                                ->defaultValue(GenericConverter::class)
                            ->end()
                            ->scalarNode('target_factory')
                                ->info('Service id of the TargetFactory')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('populators')
                                ->info('Service ids of the Populator\'s')
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
