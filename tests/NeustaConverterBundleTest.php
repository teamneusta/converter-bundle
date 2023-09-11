<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\NeustaConverterBundle;
use PHPUnit\Framework\TestCase;

class NeustaConverterBundleTest extends TestCase
{
    public function testThatBundlePathIsCurrentDir(): void
    {
        self::assertStringEndsNotWith('src', (new NeustaConverterBundle())->getPath());
    }
}
