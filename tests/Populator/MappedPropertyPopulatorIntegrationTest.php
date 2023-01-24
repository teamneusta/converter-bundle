<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\MappedPropertyPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MappedPropertyPopulatorIntegrationTest extends KernelTestCase
{
    private MappedPropertyPopulator $populator;

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
