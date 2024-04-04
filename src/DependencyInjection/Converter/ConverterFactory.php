<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Converter;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface ConverterFactory
{
    /**
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * @param ArrayNodeDefinition $node Node just under `neusta_converter.converters.<id>.<type>.`
     */
    public function addConfiguration(ArrayNodeDefinition $node): void;

    /**
     * @param array<string, mixed> $config
     */
    public function create(ContainerBuilder $container, string $id, array $config): void;
}
