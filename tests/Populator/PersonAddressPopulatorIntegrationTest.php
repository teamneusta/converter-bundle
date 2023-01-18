<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator\Populator;
use Neusta\ConverterBundle\Tests\BundleKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\UnknownType;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonAddressPopulator;

class PersonAddressPopulatorIntegrationTest extends BundleKernelTestCase
{
    /** @var Populator<User, Person, DefaultConverterContext> $populator */
    private Populator $populator;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testPopulate_regular_case(): void
    {
        $this->populator = $this->getContainer()->get('test.person.address.populator');
        $address = (new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24');

        $user = (new User())->setAddress($address);

        $person = (new Person())->setAddress(new PersonAddress());

        $this->populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }
    public function testPopulate_wrong_source_type(): void
    {
        $this->populator = $this->getContainer()->get('test.person.wrong.source.type.populator');

        $user = (new User())->setFieldWithUnknownType(new UnknownType());

        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(fieldWithUnknownType -> address\): (.*)/");
        $this->populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }
    public function testPopulate_wrong_converter(): void
    {
        $this->populator = $this->getContainer()->get('test.person.wrong.converter.populator');
        $address = (new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24');

        $user = (new User())->setAddress($address);

        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(address -> address\): (.*)/");
        $this->populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }
}
