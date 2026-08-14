<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\Phone;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class ArrayConvertingPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    /**
     * @dataProvider populatorProvider
     */
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/contact_numbers.yaml')]
    public function testPopulate(string $populatorId): void
    {
        $phone1 = '0171 2456543';
        $phone2 = '0172 2456543';
        $phone3 = '0421 2456543';
        $user = (new User())->setPhones([
            (new Phone())->setType('mobile')->setNumber($phone1),
            (new Phone())->setType('mobile')->setNumber($phone2),
            (new Phone())->setType('home')->setNumber($phone3),
        ]);
        $person = new Person();

        self::getContainer()->get($populatorId)->populate($person, $user);

        self::assertSame(
            [$phone1, $phone2, $phone3],
            array_map(static fn ($item) => $item->getPhoneNumber(), $person->getContactNumbers()),
        );
    }

    public function populatorProvider(): iterable
    {
        yield 'legacy ArrayConvertingPopulator service' => ['test.person.contactnumbers.populator'];
        yield 'array_converting populator config' => ['test.person.contactnumbers.populator.generated'];
    }
}
