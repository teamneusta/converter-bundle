<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PropertyMappingPopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populate_a_certain_source_property(): void
    {
        $populator = new PropertyMappingPopulator('age', 'ageInYears');
        $user = (new User())->setAgeInYears(37);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertEquals(37, $person->getAge());
    }

    public function test_populate_whole_source_object(): void
    {
        $populator = new PropertyMappingPopulator('address', '');
        $address = (new PersonAddress())
            ->setStreet('Street')
            ->setStreetNo('1')
            ->setCity('Capitol City')
            ->setPostalCode('12345');

        $person = new Person();

        $populator->populate($person, $address);

        self::assertEquals('Street', $person->getAddress()->getStreet());
    }
}
