<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @experimental This interface is not covered by the backward compatibility promise yet. Its shape
 *               will be settled once the first real consumers (see #39, #79) have exercised it -
 *               most likely by replacing the loose parameters with a factory context object (#108).
 */
interface PopulatorFactory
{
    /**
     * @return non-empty-string
     */
    public function getType(): string;

    /**
     * Called for both entry points: the node under `neusta_converter.populators.<id>.<type>.`
     * and the property prototype under `neusta_converter.converters.<id>.generic.properties.<target>.`.
     */
    public function addConfiguration(ArrayNodeDefinition $node): void;

    /**
     * @param array<string, mixed> $config
     */
    public function create(ContainerBuilder $container, string $id, array $config): void;
}
