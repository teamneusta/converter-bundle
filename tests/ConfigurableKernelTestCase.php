<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;
use PHPUnit\Framework\Attributes\Before;
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
     */
    #[Before]
    public function _getKernelConfigurationFromAttributes(): void
    {
        $class = new \ReflectionClass($this);
        $method = $class->getMethod($this->name());

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

        // Booting the kernel registers Symfony's ErrorHandler as exception
        // handler, which is not restored on kernel shutdown. PHPUnit 11+ flags
        // the leftover handler as a risky test, so we remove it explicitly.
        restore_exception_handler();
    }
}
