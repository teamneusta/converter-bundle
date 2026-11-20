<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\DependencyInjection;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\DependencyInjection\Converter\ConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\Converter\ReversingConverter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ReversingConverterFactory implements ConverterFactory
{
    public function getType(): string
    {
        return 'reversing';
    }

    public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void
    {
        $node
            ->children()
                ->scalarNode('target_factory')
                    ->info('Service id of the TargetFactory')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('source')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('target')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void
    {
        $container->registerAliasForArgument($id, Converter::class, $id);
        $container->register($id, ReversingConverter::class)
            ->setPublic(true)
            ->setArguments([
                '$factory' => new Reference($config['target_factory']),
                '$sourceProperty' => $config['source'],
                '$targetProperty' => $config['target'],
                '$accessor' => new Reference('property_accessor'),
            ]);
    }
}
