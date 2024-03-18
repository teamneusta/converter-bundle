<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\Address;
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

    public function test_populate_null_safety(): void
    {
        $populator = new PropertyMappingPopulator(
            'fullName',
            'fullName',
            null,
            null,
            null,
            true
        );
        $user = (new User())->setFullName(null);
        $person = new Person();
        $person->setFullName('old Name');

        $populator->populate($person, $user);

        self::assertSame('old Name', $person->getFullName());
    }

    public function test_populate_with_dot_operator(): void
    {
        $populator = new PropertyMappingPopulator(
            'placeOfResidence',
            'address.city',
            null,
            null,
            null,
            true
        );
        $user = (new User())->setAddress((new Address())->setCity('Bremen'));

        $person = new Person();

        $populator->populate($person, $user);

        self::assertSame('Bremen', $person->getPlaceOfResidence());
    }

    // This functionality will be automatically possible with Symfony 6.2 or higher.
    // public function test_populate_with_dot_operator_and_null_safety(): void
    // {
    //    $populator = new PropertyMappingPopulator(
    //        'placeOfResidence',
    //        'address?.city',
    //        null,
    //        null,
    //        null,
    //        true
    //    );
    //    $user = (new User())->setAddress(null);
    //
    //    $person = new Person();
    //    $person->setPlaceOfResidence('Old City');
    //
    //    $populator->populate($person, $user);
    //
    //    self::assertSame('Old City', $person->getPlaceOfResidence());
    // }
}
