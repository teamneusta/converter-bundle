<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/custom_contract_ambiguous.yaml')]
final class ConverterWithAmbiguousCustomContractPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testBootFailsWhenMultipleCustomContractInterfacesAreImplemented(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('implements multiple custom populator contract interfaces');

        self::bootKernel();
    }
}
