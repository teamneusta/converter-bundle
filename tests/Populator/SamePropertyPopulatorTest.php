<?php

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\SamePropertyPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SamePropertyPopulatorTest extends TestCase
{
    use ProphecyTrait;

    /** @var SamePropertyPopulator<User, Person, DefaultConverterContext> */
    private SamePropertyPopulator $populator;

    protected function setUp(): void
    {
    }

    public function testPopulate_regular_case(): void
    {
        $this->populator = new SamePropertyPopulator('fullName');
        $user = (new User())->setFullName('Max Mustermann');
        $person = (new Person());
        $person->setFullName('');

        $this->populator->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }

    public function testPopulate_sourceGetter_does_not_exist_case(): void
    {
        $this->populator = new SamePropertyPopulator('age');
        $user = new User();
        $person = $this->prophesize(Person::class);

        $this->populator->populate($person->reveal(), $user);

        $person->setAge(Argument::any())->shouldNotHaveBeenCalled();
    }
}
