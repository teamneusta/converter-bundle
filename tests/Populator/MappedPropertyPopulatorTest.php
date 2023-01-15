<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\MappedPropertyPopulator;
use Neusta\ConverterBundle\Populator\SamePropertyPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MappedPropertyPopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populate(): void
    {
        $populator = new MappedPropertyPopulator('age', 'ageInYears');
        $user = (new User())->setAgeInYears(37);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertEquals(37, $person->getAge());
    }
}
