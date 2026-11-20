<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\DependencyInjection\Compiler\DebugInfoPass;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayPropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ContextMappingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeustaConverterBundle extends Bundle
{
    public const ALIAS = 'neusta_converter';

    private ?NeustaConverterExtension $converterExtension = null;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DebugInfoPass());
    }

    /**
     * Must return the very same instance on every call: other bundles register their own factories
     * on it from their `build()` method, and those registrations have to survive until the config
     * tree is built.
     */
    public function getContainerExtension(): NeustaConverterExtension
    {
        return $this->converterExtension ??= new NeustaConverterExtension(new FactoryRegistry(
            [
                new GenericConverterFactory(),
            ],
            [
                new PropertyMappingPopulatorFactory(),
                new ArrayPropertyMappingPopulatorFactory(),
                new ConvertingPopulatorFactory(),
                new ArrayConvertingPopulatorFactory(),
                new ContextMappingPopulatorFactory(),
            ],
        ));
    }
}
