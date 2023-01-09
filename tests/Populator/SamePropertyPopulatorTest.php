<?php

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\SamePropertyPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SamePropertyPopulatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var SamePropertyPopulator<User, Person, DefaultConverterContext> */
    private SamePropertyPopulator $populator;

    public function testPopulate_regular_case(): void
    {
        $this->populator = new SamePropertyPopulator('fullName');
        $user = (new User())->setFullName('Max Mustermann');
        $person = (new Person());
        $person->setFullName('');

        $this->populator->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }

    public function testPopulate_different_type(): void
    {
        $this->populator = new SamePropertyPopulator('address');
        $user = (new User())->setAddress(new Address());
        $person = (new Person())->setAddress(new PersonAddress());

        $this->populator->populate($person, $user);

        self::assertNotEquals($user->getAddress(), $person->getAddress());
    }
}
