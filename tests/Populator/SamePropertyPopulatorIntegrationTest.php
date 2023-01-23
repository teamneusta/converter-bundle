<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\SamePropertyPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SamePropertyPopulatorIntegrationTest extends KernelTestCase
{
    /** @var SamePropertyPopulator<User, Person, DefaultConverterContext> $populator */
    private SamePropertyPopulator $populator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->populator = self::getContainer()->get('test.person.fullName.populator');
    }

    public function testPopulate(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $person = (new Person());
        $person->setFullName('');

        $this->populator->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }
}
