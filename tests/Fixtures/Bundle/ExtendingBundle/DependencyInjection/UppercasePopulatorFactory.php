<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyPopulatorFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\Mapper\UppercaseMapper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

/**
 * A populator type contributed by another bundle, expressed as a `PropertyMappingPopulator` with a
 * custom mapper - the cheap way to add a type (cf. the `LocalizedPropertyMappingPopulator` in #79).
 */
final class UppercasePopulatorFactory extends PropertyMappingPopulatorFactory implements PropertyPopulatorFactory
{
    public function getType(): string
    {
        return 'uppercase';
    }

    public function addPropertyConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->booleanNode('trim')
                    ->info('Trim the value before uppercasing it')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
    }

    protected function getMapperDefinition(array $config): Definition
    {
        return (new Definition(UppercaseMapper::class))->setArguments([
            '$trim' => $config['trim'] ?? false,
        ]);
    }
}
