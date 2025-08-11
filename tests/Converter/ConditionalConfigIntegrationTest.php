<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class ConditionalConfigIntegrationTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_property.yaml')]
    public function test_property_condition_match(): void
    {
        $user = $this->createUser(age: 18);

        $person = self::getContainer()->get('person_converter')->convert($user);

        self::assertSame('Max Mustermann', $person->getFullName());
        self::assertSame(18, $person->getAge());
        self::assertSame('28217', $person->getAddress()->getPostalCode());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_property.yaml')]
    public function test_property_condition_mismatch(): void
    {
        $user = $this->createUser(age: 39);

        $person = self::getContainer()->get('person_converter')->convert($user);

        self::assertSame('Max Mustermann', $person->getFullName());
        self::assertSame(39, $person->getAge());
        self::assertNull($person->getAddress());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_expression.yaml')]
    public function test_expression_condition_match(): void
    {
        $user = $this->createUser(age: 23);

        $person = self::getContainer()->get('person_converter')->convert($user);

        self::assertSame('Max Mustermann', $person->getFullName());
        self::assertSame(23, $person->getAge());
        self::assertSame('28217', $person->getAddress()->getPostalCode());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_expression.yaml')]
    public function test_expression_condition_mismatch(): void
    {
        $user = $this->createUser(age: 15);

        $person = self::getContainer()->get('person_converter')->convert($user);

        self::assertSame('Max Mustermann', $person->getFullName());
        self::assertSame(15, $person->getAge());
        self::assertNull($person->getAddress());
    }

    private function createUser(int $age): User
    {
        return (new User())
            ->setFullName('Max Mustermann')
            ->setAgeInYears($age)
            ->setAddress((new Address())
                ->setCity('Bremen')
                ->setPostalCode('28217')
                ->setStreet('Konsul-Smidt-Straße')
                ->setStreetNo('24'));
    }
}
