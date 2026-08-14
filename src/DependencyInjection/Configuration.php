<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function __construct(
        private readonly FactoryRegistry $factories,
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

        foreach ($this->factories->getConverterFactories() as $type => $factory) {
            $factory->addConfiguration($converterNodeBuilder->children()->arrayNode($type), $this->factories);
        }

        $converterNodeBuilder
            ->validate()
                ->ifTrue(static fn (array $v) => 1 !== \count($v))
                ->thenInvalid('Exactly one converter type must be set for a converter.')
            ->end()
        ;
    }

    private function addDeprecatedConverterSection(ArrayNodeDefinition $rootNode): void
    {
        $converterNodeBuilder = $rootNode
            ->children()
                ->arrayNode('converter')
                    ->setDeprecated('teamneusta/converter-bundle', '1.11', 'Please use "neusta_converter.converters" instead.')
                    ->info('Converter configuration')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
        ;

        // The deprecated section reuses the generic factory's tree, flattened one level up,
        // so it automatically inherits every feature the "generic" converter type gains.
        $this->factories->getConverterFactory(GenericConverterFactory::TYPE)?->addConfiguration($converterNodeBuilder, $this->factories);

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

        foreach ($this->factories->getPopulatorFactories() as $type => $factory) {
            $typeNode = $populatorNodeBuilder->children()->arrayNode($type);

            $typeNode
                ->children()
                    ->scalarNode('target')
                        ->info('Name of the target property')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ;

            $factory->addConfiguration($typeNode);

            if ($factory instanceof PropertyPopulatorFactory) {
                $factory->addPropertyConfiguration($typeNode);
            }
        }

        $populatorNodeBuilder
            ->validate()
                ->ifTrue(static fn (array $v) => 1 !== \count($v))
                ->thenInvalid('Exactly one populator type must be set for a populator.')
            ->end()
        ;
    }

    private function addDeprecatedPopulatorSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('populator')
                    ->setDeprecated('teamneusta/converter-bundle', '1.11', 'Please use "neusta_converter.populators" instead.')
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
                                        ->then(fn () => ['source' => null, 'source_array_item' => null, 'skip_null' => false])
                                    ->end()
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(fn (string $v) => ['source' => $v, 'source_array_item' => null, 'skip_null' => false])
                                    ->end()
                                    ->children()
                                        ->scalarNode('source')
                                            ->defaultValue(null)
                                        ->end()
                                        ->scalarNode('source_array_item')
                                            ->defaultValue(null)
                                        ->end()
                                        ->booleanNode('skip_null')
                                            ->defaultFalse()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('condition')
                                ->info('Condition for the ConditionalPopulator decorator')
                                ->children()
                                    ->scalarNode('property')
                                        ->info('Property of source or target object that should be checked')
                                        ->defaultNull()
                                    ->end()
                                    ->enumNode('property_base')
                                        ->info('Base object of the property: "source" or "target"')
                                        ->values(['source', 'target'])
                                        ->defaultValue('source')
                                    ->end()
                                    ->scalarNode('expected_value')
                                        ->info('Expected value for the property check')
                                        ->defaultNull()
                                    ->end()
                                    ->scalarNode('expression')
                                        ->info('Symfony Expression Language condition')
                                        ->defaultNull()
                                    ->end()
                                ->end()
                                ->validate()
                                    ->ifTrue(fn ($c) => isset($c['property'], $c['expression']))
                                    ->thenInvalid('You can only define either "property" or "expression", not both.')
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
