<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator;

use Neusta\ConverterBundle\DependencyInjection\Populator\PopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * A property populator type that does *not* extend `PropertyMappingPopulatorFactory` - proves that
 * `properties.<target>.default` is rejected purely via the `PropertyPopulatorFactory` contract, for
 * types that don't happen to share the built-in convenience base class.
 */
final class NonMappingPropertyPopulatorFactory implements PopulatorFactory, PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'non_mapping';
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
    }

    public function addPropertyConfiguration(ArrayNodeDefinition $node): void
    {
    }

    public function supportsDefaultValue(): bool
    {
        return false;
    }

    public function create(ContainerBuilder $container, string $id, array $config): void
    {
        $container->register($id, PropertyMappingPopulator::class)
            ->setArguments([
                '$targetProperty' => $config['target'],
                '$sourceProperty' => $config['target'],
                '$defaultValue' => null,
                '$mapper' => null,
                '$accessor' => new Reference('property_accessor'),
                '$skipNull' => false,
            ]);
    }
}
