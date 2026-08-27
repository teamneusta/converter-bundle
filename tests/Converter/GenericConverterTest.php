<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;

class GenericConverterTest extends TestCase
{
    /** @var Converter<User, Person, GenericContext> */
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new GenericConverter(new PersonFactory(), [new PersonNamePopulator()]);
    }

    public function testConvert(): void
    {
        // Test Fixture
        $source = (new User())->setFirstname('Max')->setLastname('Mustermann');

        // Test Execution
        $target = $this->converter->convert($source);

        // Test Assertion
        self::assertEquals('Max Mustermann', $target->getFullName());
    }

    public function testConvertWithContext(): void
    {
        // Test Fixture
        $source = (new User())->setFirstname('Max')->setLastname('Mustermann');
        $ctx = (new GenericContext())->setValue('separator', ', ');

        // Test Execution
        $target = $this->converter->convert($source, $ctx);

        // Test Assertion
        self::assertEquals('Max, Mustermann', $target->getFullName());
    }

    public function testConvertWithSameContextPropertyNames(): void
    {
        $converter = new GenericConverter(new PersonFactory(), [new ContextMappingPopulator('locale', 'locale')]);

        // Test Fixture
        $source = new User();
        $ctx = (new GenericContext())->setValue('locale', 'en');

        // Test Execution
        $target = $converter->convert($source, $ctx);

        // Test Assertion
        self::assertEquals('en', $target->getLocale());
    }

    public function testConvertWithDifferentContextPropertyNames(): void
    {
        $converter = new GenericConverter(new PersonFactory(), [new ContextMappingPopulator('locale', 'language')]);

        // Test Fixture
        $source = new User();
        $ctx = (new GenericContext())->setValue('language', 'en');

        // Test Execution
        $target = $converter->convert($source, $ctx);

        // Test Assertion
        self::assertEquals('en', $target->getLocale());
    }

    public function testConvertTriggersDeprecationForNonContextCtx(): void
    {
        $converter = new GenericConverter(new PersonFactory(), []);
        $source = new User();
        $ctx = new \stdClass();

        $deprecations = $this->captureDeprecations(static fn () => $converter->convert($source, $ctx));

        self::assertCount(1, $deprecations);
        self::assertStringContainsString('stdClass', $deprecations[0]);
        self::assertStringContainsString(Context::class, $deprecations[0]);
    }

    public function testConvertDoesNotTriggerDeprecationForContext(): void
    {
        $converter = new GenericConverter(new PersonFactory(), []);
        $source = new User();
        $ctx = Context::create();

        $deprecations = $this->captureDeprecations(static fn () => $converter->convert($source, $ctx));

        self::assertCount(0, $deprecations);
    }

    public function testConvertDoesNotTriggerAdditionalDeprecationForGenericContext(): void
    {
        $converter = new GenericConverter(new PersonFactory(), []);
        $source = new User();
        $ctx = new GenericContext();

        $deprecations = $this->captureDeprecations(static fn () => $converter->convert($source, $ctx));

        self::assertCount(0, $deprecations);
    }

    /**
     * @return list<string>
     */
    private function captureDeprecations(\Closure $closure): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $closure();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
