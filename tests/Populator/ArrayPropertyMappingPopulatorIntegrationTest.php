<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\ArrayPropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Hobby;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ArrayPropertyMappingPopulatorIntegrationTest extends KernelTestCase
{
    private ArrayPropertyMappingPopulator $populator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->populator = self::getContainer()->get('test.person.activities.populator');
    }

    public function testPopulate(): void
    {
        $user = (new User())->setHobbies([
            (new Hobby())->setLabel('reading'),
            (new Hobby())->setLabel('swimming'),
            (new Hobby())->setLabel('computers'),
        ]);
        $person = new Person();

        $this->populator->populate($person, $user);

        self::assertEquals(
            [
                'reading',
                'swimming',
                'computers',
            ],
            $person->getActivities()
        );
    }
}
