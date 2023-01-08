<?php

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Populator\SamePropertyPopulator;
use Neusta\ConverterBundle\Tests\BundleKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SamePropertyPopulatorIntegrationTest extends BundleKernelTestCase
{
    private SamePropertyPopulator $populator;
    protected function setUp(): void
    {
        parent::setUp();
        $this->populator = $this->getContainer()->get('test.person.fullName.populator');
    }

    /** @var SamePropertyPopulator<User, Person, DefaultConverterContext> */

    public function testPopulate(): void
    {
        $user = (new User())->setFullName('Max Mustermann');
        $person = (new Person());
        $person->setFullName('');

        $this->populator->populate($person, $user);

        self::assertEquals('Max Mustermann', $person->getFullName());
    }
}
