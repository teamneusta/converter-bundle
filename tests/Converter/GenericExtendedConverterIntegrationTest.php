<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

#[ConfigureContainer(__DIR__ . '/../Fixtures/Config/person.yaml')]
class GenericExtendedConverterIntegrationTest extends ConfigurableKernelTestCase
{
    /**
     * @dataProvider converterProvider
     */
    public function test_convert_with_skip_null(string $converterId): void
    {
        // Test Fixture
        $source = (new User())
            ->setFullName(null)
            ->setAgeInYears(null)
            ->setEmail(null);

        // Test Execution
        $target = self::getContainer()->get($converterId)->convert($source);

        // Test Assertion
        self::assertInstanceOf(Person::class, $target);
        self::assertSame('Hans Herrmann', $target->getFullName());
        self::assertSame('default@test.de', $target->getMail());
        self::assertSame(39, $target->getAge());
    }

    public function converterProvider(): iterable
    {
        yield 'extended person converter' => ['test.person.converter.extended'];
        yield 'legacy extended person converter' => ['legacy.test.person.converter.extended'];
    }
}
