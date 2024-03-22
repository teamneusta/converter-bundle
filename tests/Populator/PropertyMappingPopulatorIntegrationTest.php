<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class PropertyMappingPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/full_name.yaml')]
    public function testPopulate(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $person = new Person();

        self::getContainer()->get('test.person.fullName.populator')->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }
}
