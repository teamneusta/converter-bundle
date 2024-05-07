<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\Mapper\ArrayPropertyMapper;
use Neusta\ConverterBundle\Populator\Mapper\ConverterMapper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ArrayConvertingPopulatorFactory extends PropertyMappingPopulatorFactory implements PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'array_converting';
    }

    public function addPropertyConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('converter')
                    ->info('Service id of the internal "Converter"')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('source_array_item')
                    ->defaultValue(null)
                ->end()
            ->end()
        ;
    }

    protected function getMapperDefinition(array $config): ?Definition
    {
        $config = $config[$this->getType()];

        return (new Definition(ArrayPropertyMapper::class))->setArguments([
            '$sourceArrayItemProperty' => $config['source_array_item'] ?? null,
            '$arrayItemAccessor' => null,
            '$mapper' => (new Definition(ConverterMapper::class))->setArguments([
                '$converter' => new Reference($config['converter']),
            ]),
        ]);
    }
}
