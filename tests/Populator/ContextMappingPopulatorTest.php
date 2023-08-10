<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;

class ContextMappingPopulatorTest extends TestCase
{
    public function test_populateWithSameContextPropertyNames(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale');
        $user = new User();
        $person = new Person();
        $ctx = (new GenericContext())->setValue('locale', 'en');

        $populator->populate($person, $user, $ctx);

        self::assertEquals('en', $person->getLocale());
    }

    public function test_populateWithDifferentContextPropertyNames(): void
    {
        $populator = new ContextMappingPopulator('locale', 'language');
        $user = new User();
        $person = new Person();
        $ctx = (new GenericContext())->setValue('language', 'en');

        $populator->populate($person, $user, $ctx);

        self::assertEquals('en', $person->getLocale());
    }

    public function test_populateWithoutContextPropertyNames(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale');
        $user = new User();
        $person = new Person();
        $ctx = new GenericContext();

        $populator->populate($person, $user, $ctx);

        self::assertNull($person->getLocale());
    }

    public function test_populateWithoutContext(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale');
        $user = new User();
        $person = new Person();

        $populator->populate($person, $user);

        self::assertNull($person->getLocale());
    }
}
