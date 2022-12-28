<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleKernelTestCase extends KernelTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel([
            'debug'       => false,
        ]);
    }
}
