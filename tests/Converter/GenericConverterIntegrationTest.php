<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/person.yaml')]
class GenericConverterIntegrationTest extends ConfigurableKernelTestCase
{
    /**
     * @dataProvider converterProvider
     */
    public function testConvert(string $converterId): void
    {
        // Test Fixture
        $source = (new User())->setFirstname('Max')->setLastname('Mustermann');

        // Test Execution
        $target = self::getContainer()->get($converterId)->convert($source);

        // Test Assertion
        self::assertInstanceOf(Person::class, $target);
        self::assertSame('Max Mustermann', $target->getFullName());
    }

    /**
     * @dataProvider converterProvider
     */
    public function testConvertWithContext(string $converterId): void
    {
        // Test Fixture
        $source = (new User())->setFirstname('Max')->setLastname('Mustermann');
        $ctx = (new GenericContext())
            ->setValue('group', 'foobar')
            ->setValue('language', 'de')
            ->setValue('separator', ', ');

        // Test Execution
        $target = self::getContainer()->get($converterId)->convert($source, $ctx);

        // Test Assertion
        self::assertInstanceOf(Person::class, $target);
        self::assertSame('foobar', $target->getGroup());
        self::assertSame('de', $target->getLocale());
        self::assertSame('Max, Mustermann', $target->getFullName());
    }

    public function converterProvider(): iterable
    {
        yield 'person converter' => ['test.person.converter'];
        yield 'legacy person converter' => ['legacy.test.person.converter'];
    }
}
