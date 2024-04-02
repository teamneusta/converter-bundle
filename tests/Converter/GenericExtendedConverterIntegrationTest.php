<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/person.yaml')]
#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/address.yaml')]
#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/contact_numbers.yaml')]
class GenericExtendedConverterIntegrationTest extends ConfigurableKernelTestCase
{
    public function testConvert_with_null_safety_property(): void
    {
        /** @var Converter<User, Person, GenericContext> $converter */
        $converter = self::getContainer()->get('test.person.converter.extended');

        // Test Fixture
        $source = (new User())
            ->setFullName(null)
            ->setAgeInYears(null);

        // Test Execution
        $target = $converter->convert($source);

        // Test Assertion
        self::assertEquals('Hans Herrmann', $target->getFullName());
    }
}
