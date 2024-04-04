<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @param array<string, ConverterFactory> $converterFactories
     * @param array<string, PopulatorFactory> $populatorFactories
     */
    public function __construct(
        private readonly array $converterFactories,
        private readonly array $populatorFactories,
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(NeustaConverterBundle::ALIAS);
        $rootNode = $treeBuilder->getRootNode();

        $this->addConverterSection($rootNode);
        $this->addDeprecatedConverterSection($rootNode);
        $this->addPopulatorSection($rootNode);
        $this->addDeprecatedPopulatorSection($rootNode);

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
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
        ;

        foreach ($this->converterFactories as $type => $factory) {
            $factory->addConfiguration($converterNodeBuilder->children()->arrayNode($type));
        }

        $converterNodeBuilder
            ->validate()
                ->ifTrue(fn ($v) => \count($v) > 1)
                ->thenInvalid('You cannot set multiple converter types for the same converter.')
            ->end()
        ;
    }

    private function addDeprecatedConverterSection(ArrayNodeDefinition $rootNode): void
    {
        $converterNodeBuilder = $rootNode
            ->children()
                ->arrayNode('converter')
                    ->setDeprecated('teamneusta/converter-bundle', '1.5', 'Please use "neusta_converter.converters" instead.')
                    ->info('Converter configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
        ;

        $this->converterFactories['generic']->addConfiguration($converterNodeBuilder);

        $converterNodeBuilder
            ->children()
                ->scalarNode('converter')
                    ->info('Class name of the Converter implementation')
                    ->defaultValue(GenericConverter::class)
                ->end()
            ->end()
        ;
    }

    private function addPopulatorSection(ArrayNodeDefinition $rootNode): void
    {
        $populatorNodeBuilder = $rootNode
            //->fixXmlConfig('populator') // Todo: only possible once deprecated config got removed
            ->children()
                ->arrayNode('populators')
                    ->info('Populator configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
        ;

        foreach ($this->populatorFactories as $type => $factory) {
            $factory->addConfiguration($populatorNodeBuilder->children()->arrayNode($type));
        }

        $populatorNodeBuilder
            ->validate()
                ->ifTrue(fn ($v) => \count($v) > 1)
                ->thenInvalid('You cannot set multiple populator types for the same populator.')
            ->end()
        ;
    }

    private function addDeprecatedPopulatorSection(ArrayNodeDefinition $rootNode): void
    {
        $populatorNodeBuilder = $rootNode
            ->children()
                ->arrayNode('populator')
                    ->setDeprecated('teamneusta/converter-bundle', '1.5', 'Please use "neusta_converter.populators" instead.')
                    ->info('Populator configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
        ;

        $this->populatorFactories['array_converting']->addConfiguration($populatorNodeBuilder);

        $populatorNodeBuilder
            ->children()
                ->enumNode('populator')
                    ->info('class of the "Populator" implementation')
                    ->values([ConvertingPopulator::class, ArrayConvertingPopulator::class])
                    ->defaultValue(ConvertingPopulator::class)
                ->end()
            ->end()
            ->validate()
                ->ifTrue(fn (array $c) => ArrayConvertingPopulator::class !== $c['populator'] && !empty($c['property'][array_key_first($c['property'])]['source_array_item']))
                ->thenInvalid('The "property.<target>.source_array_item" option is only supported for array converting populators.')
            ->end()
        ;
    }
}
