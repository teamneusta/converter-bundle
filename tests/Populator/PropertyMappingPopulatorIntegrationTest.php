<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PropertyMappingPopulatorIntegrationTest extends KernelTestCase
{
    private PropertyMappingPopulator $populator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->populator = self::getContainer()->get('test.person.fullName.populator');
    }

    public function testPopulate(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $person = new Person();

        $this->populator->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }
}
