<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/custom_contract_multiple_methods.yaml')]
final class ConverterWithMultipleMethodsCustomContractIntegrationTest extends ConfigurableKernelTestCase
{
    public function testBootFailsWhenCustomContractInterfaceDeclaresMultipleMethods(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must declare exactly one method');

        self::bootKernel();
    }
}
