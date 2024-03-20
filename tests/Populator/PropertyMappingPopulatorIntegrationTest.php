<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use TestKernel;

class PropertyMappingPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testPopulate(): void
    {
        self::bootKernel(['config' => function(TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/../Fixtures/Config/full_name.yaml');
        }]);

        $user = (new User())->setFullName('Max Mustermann');
        $person = new Person();

        self::getContainer()->get('test.person.fullName.populator')->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }
}
