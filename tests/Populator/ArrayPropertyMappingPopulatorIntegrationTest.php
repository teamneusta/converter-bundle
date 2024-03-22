<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\Hobby;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class ArrayPropertyMappingPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/activities.yaml')]
    public function testPopulate(): void
    {
        $user = (new User())->setHobbies([
            (new Hobby())->setLabel('reading'),
            (new Hobby())->setLabel('swimming'),
            (new Hobby())->setLabel('computers'),
        ]);
        $person = new Person();

        self::getContainer()->get('test.person.activities.populator')->populate($person, $user);

        self::assertSame(
            [
                'reading',
                'swimming',
                'computers',
            ],
            $person->getActivities(),
        );
    }
}
