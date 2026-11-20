<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;
use Neusta\ConverterBundle\Tests\Support\Attribute\RegisterBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ConfigurableKernelTestCase extends KernelTestCase
{
    /** @var list<ConfigureContainer|RegisterBundle> */
    private static iterable $kernelConfigurations = [];

    protected static function getKernelClass(): string
    {
        return \TestKernel::class;
    }

    protected static function createKernel(array $options = []): \TestKernel
    {
        $kernel = parent::createKernel($options);
        \assert($kernel instanceof \TestKernel);

        foreach (self::$kernelConfigurations as $configuration) {
            $configuration->configure($kernel);
        }

        $kernel->handleOptions($options);

        return $kernel;
    }

    /**
     * @internal
     *
     * @before
     */
    public function _getKernelConfigurationFromAttributes(): void
    {
        $class = new \ReflectionClass($this);
        $method = $class->getMethod($this->getName(false));

        $attributes = [];
        // Bundles first: a config file may rely on a config tree that a bundle contributes.
        foreach ([RegisterBundle::class, ConfigureContainer::class] as $attributeClass) {
            foreach ($class->getAttributes($attributeClass) as $attribute) {
                $attributes[] = $attribute->newInstance();
            }

            foreach ($method->getAttributes($attributeClass) as $attribute) {
                $attributes[] = $attribute->newInstance();
            }
        }

        self::$kernelConfigurations = $attributes;
    }

    protected function tearDown(): void
    {
        self::$kernelConfigurations = [];
        parent::tearDown();
    }
}
