<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonAddress;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PropertyMappingPopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populate_a_certain_source_property(): void
    {
        $populator = new PropertyMappingPopulator(
            targetProperty: 'age',
            sourceProperty: 'ageInYears',
        );
        $source = (new User())->setAgeInYears(37);
        $target = new Person();

        $populator->populate($target, $source);

        self::assertEquals(37, $target->getAge());
    }

    public function test_populate_default_value(): void
    {
        $populator = new PropertyMappingPopulator(
            targetProperty: 'fullName',
            sourceProperty: 'fullName',
            defaultValue: 'default',
        );
        $source = (new User())->setFullName(null);
        $target = new Person();

        $populator->populate($target, $source);

        self::assertSame('default', $target->getFullName());
    }

    public function test_populate_skip_null(): void
    {
        $populator = new PropertyMappingPopulator(
            targetProperty: 'fullName',
            sourceProperty: 'fullName',
            skipNull: true,
        );
        $source = (new User())->setFullName(null);
        $target = new Person();
        $target->setFullName('old Name');

        $populator->populate($target, $source);

        self::assertSame('old Name', $target->getFullName());
    }

    public function test_populate_with_sub_fields(): void
    {
        $populator = new PropertyMappingPopulator(
            targetProperty: 'placeOfResidence',
            sourceProperty: 'address.city',
        );
        $source = (new User())->setAddress((new Address())->setCity('Bremen'));
        $target = new Person();

        $populator->populate($target, $source);

        self::assertSame('Bremen', $target->getPlaceOfResidence());
    }

    /**
     * @requires function \Symfony\Component\PropertyAccess\PropertyPath::isNullSafe
     */
    public function test_populate_skip_null_with_sub_fields_and_null_safety(): void
    {
        $populator = new PropertyMappingPopulator(
            targetProperty: 'placeOfResidence',
            sourceProperty: 'address?.city',
            skipNull: true,
        );
        $source = (new User())->setAddress(null);
        $target = new Person();
        $target->setPlaceOfResidence('Old City');

        $populator->populate($target, $source);

        self::assertSame('Old City', $target->getPlaceOfResidence());
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

        self::assertSame($address, $person->getAddress());
    }
}
