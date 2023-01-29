<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator\Populator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\UnknownType;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PersonAddressPopulatorIntegrationTest extends KernelTestCase
{
    public function testPopulate_regular_case(): void
    {
        /** @var Populator<User, Person, GenericContext> $populator */
        $populator = self::getContainer()->get('test.person.address.populator');
        $address = (new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24');

        $user = (new User())->setAddress($address);

        $person = (new Person())->setAddress(new PersonAddress());

        $populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }

    public function testPopulate_wrong_source_type(): void
    {
        /** @var Populator<User, Person, GenericContext> $populator */
        $populator = self::getContainer()->get('test.person.wrong.source.type.populator');

        $user = (new User())->setFieldWithUnknownType(new UnknownType());

        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(fieldWithUnknownType -> address\): (.*)/");
        $populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }

    public function testPopulate_wrong_converter(): void
    {
        /** @var Populator<User, Person, GenericContext> $populator */
        $populator = self::getContainer()->get('test.person.wrong.converter.populator');
        $address = (new Address())
            ->setCity('Bremen')
            ->setPostalCode('28217')
            ->setStreet('Konsul-Smidt-Straße')
            ->setStreetNo('24');

        $user = (new User())->setAddress($address);

        $person = (new Person())->setAddress(new PersonAddress());

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessageMatches("/Population Exception \(address -> address\): (.*)/");
        $populator->populate($person, $user);

        self::assertEquals('24', $person->getAddress()->getStreetNo());
    }
}
