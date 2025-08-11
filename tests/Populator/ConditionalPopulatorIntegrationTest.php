<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class ConditionalPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_full_name.yaml')]
    public function testPopulate_condition_true(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $user->setAgeInYears(18);
        $person = new Person();

        self::getContainer()->get('test.person.fullName.conditional.populator')->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/conditional_full_name.yaml')]
    public function testPopulate_condition_false(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $user->setAgeInYears(17);
        $person = new Person();

        self::getContainer()->get('test.person.fullName.conditional.populator')->populate($person, $user);

        self::assertNull($person->getFullName());
    }
}
