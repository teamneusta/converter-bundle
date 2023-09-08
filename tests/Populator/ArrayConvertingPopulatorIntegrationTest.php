<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Phone;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ArrayConvertingPopulatorIntegrationTest extends KernelTestCase
{
    private ArrayConvertingPopulator $populator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->populator = self::getContainer()->get('test.person.contactnumbers.populator');
    }

    public function testPopulate(): void
    {
        $phone1 = (new Phone())->setType('mobile')->setNumber('0171 2456543');
        $phone2 = (new Phone())->setType('mobile')->setNumber('0172 2456543');
        $phone3 = (new Phone())->setType('home')->setNumber('0421 2456543');

        $user = (new User())->setPhones([$phone1, $phone2, $phone3]);
        $person = new Person();

        $this->populator->populate($person, $user);

        self::assertEquals(
            [
                '0171 2456543',
                '0172 2456543',
                '0421 2456543',
            ],
            array_map(
                function ($item) {
                    return $item->getPhoneNumber();
                },
                $person->getContactNumbers()
            )
        );
    }
}
