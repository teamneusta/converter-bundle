<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Exception\PopulationException;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
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

    public function test_populateWithoutContextPropertyNames_required_throws(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale', null, null, null, true);
        $user = new User();
        $person = new Person();
        $ctx = new GenericContext();

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessage('The context does not contain a value for "locale"');

        $populator->populate($person, $user, $ctx);
    }

    public function test_populate_exceptional_case(): void
    {
        $populator = new ContextMappingPopulator('unknown', 'locale');
        $user = new User();
        $person = new Person();
        $ctx = new GenericContext();
        $ctx->setValue('locale', 'de');

        $this->expectException(PopulationException::class);

        $populator->populate($person, $user, $ctx);
    }

    public function test_populateWithoutContext(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale');
        $user = new User();
        $person = new Person();

        $populator->populate($person, $user);

        self::assertNull($person->getLocale());
    }

    public function test_populateWithoutContext_required_throws(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale', null, null, null, true);
        $user = new User();
        $person = new Person();

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessage('No context was provided.');

        $populator->populate($person, $user);
    }

    public function test_populateWithNewContext(): void
    {
        $populator = new ContextMappingPopulator('age', 'age', null, null, AgeContext::class);
        $user = new User();
        $person = new Person();
        $ctx = Context::create(new AgeContext(39));

        $populator->populate($person, $user, $ctx);

        self::assertEquals(39, $person->getAge());
    }

    public function test_populateWithNewContext_missing_object(): void
    {
        $populator = new ContextMappingPopulator('age', 'age', null, null, AgeContext::class);
        $user = new User();
        $person = new Person();
        $ctx = Context::create();

        $populator->populate($person, $user, $ctx);

        self::assertNull($person->getAge());
    }

    public function test_populateWithNewContext_missing_object_required_throws(): void
    {
        $populator = new ContextMappingPopulator('age', 'age', null, null, AgeContext::class, true);
        $user = new User();
        $person = new Person();
        $ctx = Context::create();

        $this->expectException(PopulationException::class);
        $this->expectExceptionMessage(\sprintf('The context does not contain an instance of "%s"', AgeContext::class));

        $populator->populate($person, $user, $ctx);
    }

    public function test_populateWithNewContext_missing_class_throws(): void
    {
        $populator = new ContextMappingPopulator('age', 'age');
        $user = new User();
        $person = new Person();
        $ctx = Context::create(new AgeContext(39));

        $this->expectException(\LogicException::class);

        $populator->populate($person, $user, $ctx);
    }

    public function test_populateWithUnsupportedContextType_throws(): void
    {
        $populator = new ContextMappingPopulator('locale', 'locale');
        $user = new User();
        $person = new Person();

        $this->expectException(\InvalidArgumentException::class);

        $populator->populate($person, $user, new \stdClass());
    }

    public function test_populate_exceptional_case_wraps_read_failures_from_new_context(): void
    {
        $populator = new ContextMappingPopulator('locale', 'unknownProperty', null, null, AgeContext::class);
        $user = new User();
        $person = new Person();
        $ctx = Context::create(new AgeContext(39));

        $this->expectException(PopulationException::class);

        $populator->populate($person, $user, $ctx);
    }
}
