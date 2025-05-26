<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator\ConstantValuePopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ConstantValuePopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populate_constant_value_to_property(): void
    {
        $populator = new ConstantValuePopulator(
            targetProperty: 'age',
            value: 37,
        );
        $target = new Person();

        $populator->populate($target, new User());

        self::assertEquals(37, $target->getAge());
    }

    public function test_populate_with_mapper(): void
    {
        $populator = new ConstantValuePopulator(
            targetProperty: 'fullName',
            value: 'nnamretsuM',
            mapper: fn ($v) => strrev($v),
        );
        $target = new Person();

        $populator->populate($target, new User());

        self::assertSame('Mustermann', $target->getFullName());
    }

    public function test_populate_not_existing_property(): void
    {
        $populator = new ConstantValuePopulator(
            targetProperty: 'notExistingProperty',
            value: 'xxx',
            mapper: fn ($v) => strrev($v),
        );
        $target = new Person();

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessage('Population Exception (xxx -> notExistingProperty):');
        $populator->populate($target, new User());
    }
}
