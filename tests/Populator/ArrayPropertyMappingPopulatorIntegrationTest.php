<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Tests\Fixtures\Model\Hobby;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use TestKernel;

class ArrayPropertyMappingPopulatorIntegrationTest extends ConfigurableKernelTestCase
{
    public function testPopulate(): void
    {
        self::bootKernel(['config' => function(TestKernel $kernel) {
            $kernel->addTestConfig(__DIR__ . '/../Fixtures/Config/activities.yaml');
        }]);

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
