<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/custom_contract_invalid.yaml')]
final class ConverterWithInvalidCustomContractPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testBootFailsWhenNoCustomContractInterfaceIsImplemented(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('does not implement a custom populator contract interface');

        self::bootKernel();
    }
}
