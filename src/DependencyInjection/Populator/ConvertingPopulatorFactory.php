<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

final class ConvertingPopulatorFactory implements PopulatorFactory
{
    public function getType(): string
    {
        return 'converting';
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
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
                            ->then(fn () => ['source' => null])
                        ->end()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(fn (string $v) => ['source' => $v])
                        ->end()
                        ->children()
                            ->scalarNode('source')
                                ->defaultValue(null)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $targetProperty = array_key_first($config['property']);
        $sourceProperty = $config['property'][$targetProperty];

        $container->register($id, ConvertingPopulator::class)
            ->setPublic(true)
            ->setArguments([
                '$converter' => new TypedReference($config['converter'], Converter::class),
                '$targetPropertyName' => $targetProperty,
                '$sourcePropertyName' => $sourceProperty['source'] ?? $targetProperty,
                '$accessor' => new Reference('property_accessor'),
            ]);
    }
}
