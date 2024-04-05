<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Converter;

use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConverterFactory
{
    /**
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * @param ArrayNodeDefinition $node Node under `neusta_converter.converters.<id>.<type>.`
     */
    public function addConfiguration(ArrayNodeDefinition $node, FactoryRegistry $factories): void;

    /**
     * @param array<string, mixed> $config
     */
    public function create(ContainerBuilder $container, string $id, array $config, FactoryRegistry $factories): void;
}
