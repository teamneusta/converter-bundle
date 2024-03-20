<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ConfigurableKernelTestCase extends KernelTestCase
{
    /** @var list<ConfigureContainer> */
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
        foreach ($class->getAttributes(ConfigureContainer::class) as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        foreach ($method->getAttributes(ConfigureContainer::class) as $attribute) {
            $attributes[] = $attribute->newInstance();
        }

        self::$kernelConfigurations = $attributes;
    }

    protected function tearDown(): void
    {
        self::$kernelConfigurations = [];
        parent::tearDown();
    }
}
