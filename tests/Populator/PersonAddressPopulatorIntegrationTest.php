<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\UnknownType;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/person.yaml')]
#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/address.yaml')]
class PersonAddressPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testPopulate_regular_case(): void
    {
        $user = (new User())->setAddress((new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24'));
        $person = (new Person())->setAddress(new PersonAddress());

        self::getContainer()->get('test.person.address.populator')->populate($person, $user);

        self::assertSame('24', $person->getAddress()->getStreetNo());
    }

    public function testPopulate_wrong_source_type(): void
    {
        $user = (new User())->setFieldWithUnknownType(new UnknownType());
        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(fieldWithUnknownType -> address\): (.*)/");

        self::getContainer()->get('test.person.wrong.source.type.populator')->populate($person, $user);
    }

    public function testPopulate_wrong_converter(): void
    {
        $user = (new User())->setAddress((new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24'));
        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(address -> address\): (.*)/");

        self::getContainer()->get('test.person.wrong.converter.populator')->populate($person, $user);
    }
}
