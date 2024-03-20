<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TestKernel;

abstract class ConfigurableKernelTestCase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function createKernel(array $options = []): TestKernel
    {
        $kernel = parent::createKernel($options);
        \assert($kernel instanceof TestKernel);

        $kernel->handleOptions($options);

        return $kernel;
    }
}
