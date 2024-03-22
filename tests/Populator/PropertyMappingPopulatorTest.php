<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PropertyMappingPopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populate(): void
    {
        $populator = new PropertyMappingPopulator('age', 'ageInYears');
        $user = (new User())->setAgeInYears(37);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertEquals(37, $person->getAge());
    }

    public function test_populate_default_value(): void
    {
        $populator = new PropertyMappingPopulator('fullName', 'fullName', 'default');
        $user = (new User())->setFullName(null);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertSame('default', $person->getFullName());
    }
}
