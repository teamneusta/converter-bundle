<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

interface PropertyPopulatorFactory
{
    /**
     * @param ArrayNodeDefinition $node Node under `neusta_converter.converters.<id>.generic.properties.<type>.`
     */
    public function addPropertyConfiguration(ArrayNodeDefinition $node): void;
}
