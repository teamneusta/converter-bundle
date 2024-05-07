<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeustaConverterBundle extends Bundle
{
    public const ALIAS = 'neusta_converter';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): NeustaConverterExtension
    {
        return new NeustaConverterExtension(new FactoryRegistry(
            [
                new GenericConverterFactory(),
            ],
            [
                new PropertyMappingPopulatorFactory(),
                new ConvertingPopulatorFactory(),
                new ArrayConvertingPopulatorFactory(),
            ],
        ));
    }
}
