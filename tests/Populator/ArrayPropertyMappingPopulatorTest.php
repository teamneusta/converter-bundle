<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Populator\ArrayPropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Hobby;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class ArrayPropertyMappingPopulatorTest extends TestCase
{
    use ProphecyTrait;

    public function test_populateWithoutInnerProperty(): void
    {
        $populator = new ArrayPropertyMappingPopulator('favouriteMovies', 'favouriteMovies');
        $user = (new User())->setFavouriteMovies([
            'Once upon a time in the West',
            'Once upon a time in America',
        ]);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertEquals([
            'Once upon a time in the West',
            'Once upon a time in America',
        ], $person->getFavouriteMovies());
    }

    public function test_populateWithInnerProperty(): void
    {
        $populator = new ArrayPropertyMappingPopulator('activities', 'hobbies','label');
        $user = (new User())->setHobbies([
            (new Hobby())->setLabel('reading'),
            (new Hobby())->setLabel('swimming'),
            (new Hobby())->setLabel('computers'),
        ]);
        $person = new Person();

        $populator->populate($person, $user);

        self::assertEquals(['reading', 'swimming', 'computers'], $person->getActivities());
    }
}
