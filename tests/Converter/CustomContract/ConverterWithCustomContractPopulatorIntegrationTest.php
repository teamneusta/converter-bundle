<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/custom_contract.yaml')]
class ConverterWithCustomContractPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testConvertWithSingleMethodCustomContract(): void
    {
        // Arrange
        $source = (new User())->setFirstname('Max')->setLastname('Mustermann');
        $ctx = (new GenericContext())->setValue('separator', ', ');

        // Act
        $target = self::getContainer()->get('test.person.converter')->convert($source, $ctx);

        // Assert
        self::assertInstanceOf(Person::class, $target);
        self::assertSame('Max, Mustermann', $target->getFullName());
    }
}
