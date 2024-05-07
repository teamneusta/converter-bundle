<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\Mapper\ConverterMapper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class ConvertingPopulatorFactory extends PropertyMappingPopulatorFactory implements PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'converting';
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
            ->end()
        ;
    }

    protected function getMapperDefinition(array $config): ?Definition
    {
        $config = $config[$this->getType()];

        return (new Definition(ConverterMapper::class))->setArguments([
            '$converter' => new Reference($config['converter']),
        ]);
    }
}
