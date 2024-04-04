<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface PopulatorFactory
{
    /**
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * @param ArrayNodeDefinition $node Node just under `neusta_converter.populators.<id>.<type>.`
     */
    public function addConfiguration(ArrayNodeDefinition $node): void;

    /**
     * @param array<string, mixed> $config
     */
    public function create(ContainerBuilder $container, string $id, array $config): void;
}
