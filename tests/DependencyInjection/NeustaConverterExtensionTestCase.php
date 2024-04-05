<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\PropertyMappingPopulatorFactory;

abstract class NeustaConverterExtensionTestCase extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        $converterFactories = $this->getConverterFactories();
        if (!\in_array($legacyFactory = new GenericConverterFactory(), $converterFactories, false)) {
            $converterFactories[] = $legacyFactory;
        }

        $populatorFactories = $this->getPopulatorFactories();
        if (!\in_array($mandatoryFactory = new PropertyMappingPopulatorFactory(), $populatorFactories, false)) {
            $populatorFactories[] = $mandatoryFactory;
        }
        if (!\in_array($legacyFactory = new ArrayConvertingPopulatorFactory(), $populatorFactories, false)) {
            $populatorFactories[] = $legacyFactory;
        }

        return [
            new NeustaConverterExtension(new FactoryRegistry(
                $converterFactories,
                $populatorFactories,
            )),
        ];
    }

    protected function getConverterFactories(): array
    {
        return [];
    }

    protected function getPopulatorFactories(): array
    {
        return [];
    }

    /**
     * Assert that the ContainerBuilder for this test has a public service definition with the given id and class.
     */
    protected function assertContainerBuilderHasPublicService(string $serviceId, ?string $expectedClass = null): void
    {
        $this->assertContainerBuilderHasService($serviceId, $expectedClass);
        $this->assertTrue(
            $this->container->getDefinition($serviceId)->isPublic(),
            sprintf('service definition "%s" is "public"', $serviceId),
        );
    }
}
