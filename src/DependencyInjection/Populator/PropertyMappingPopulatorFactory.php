<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PropertyMappingPopulatorFactory implements PopulatorFactory
{
    public function getType(): string
    {
        return 'property_mapping';
    }

    final public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->beforeNormalization()
                ->ifNull()
                ->then(fn () => ['source' => null, 'default' => null, 'skip_null' => false])
            ->end()
            ->beforeNormalization()
                ->ifString()
                ->then(fn (string $v) => ['source' => $v, 'default' => null, 'skip_null' => false])
            ->end()
            ->children()
                ->scalarNode('source')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('default')
                    ->defaultValue(null)
                ->end()
                ->booleanNode('skip_null')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
    }

    final public function create(ContainerBuilder $container, string $id, array $config): void
    {
        if (str_ends_with($config['target'], '?')) {
            $config['target'] = substr($config['target'], 0, -1);
            $config['skip_null'] = true;
        }

        $container->register($id, PropertyMappingPopulator::class)
            ->setPublic(true)
            ->setArguments([
                '$targetProperty' => $config['target'],
                '$sourceProperty' => $config['source'] ?? $config['target'],
                '$defaultValue' => $config['default'],
                '$mapper' => $this->getMapperDefinition($config),
                '$accessor' => new Reference('property_accessor'),
                '$skipNull' => $config['skip_null'],
            ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function getMapperDefinition(array $config): ?Definition
    {
        return null;
    }
}
