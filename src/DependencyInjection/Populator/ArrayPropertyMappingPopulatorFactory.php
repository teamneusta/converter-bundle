<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\Mapper\ArrayPropertyMapper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ArrayPropertyMappingPopulatorFactory extends PropertyMappingPopulatorFactory implements PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'array_property_mapping';
    }

    public function addPropertyConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('source_array_item')
                    ->defaultValue(null)
                ->end()
            ->end()
        ;
    }

    protected function supportsDefaultValue(): bool
    {
        return false;
    }

    protected function getMapperDefinition(array $config): Definition
    {
        return (new Definition(ArrayPropertyMapper::class))->setArguments([
            '$sourceArrayItemProperty' => $config['source_array_item'] ?? null,
            '$arrayItemAccessor' => new Reference('property_accessor'),
            '$mapper' => null,
        ]);
    }
}
