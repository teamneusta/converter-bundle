<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\CacheManagement\DefaultCacheManagement;
use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\CachedConverter;
use Neusta\ConverterBundle\Converter\DefaultConverter;
use Neusta\ConverterBundle\Tests\Fixtures\CacheManagement\UserKeyFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;

class CachedConverterTest extends TestCase
{
    /** @var Converter<User, Person> */
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new CachedConverter(
            new DefaultConverter(
                new PersonFactory(),
                [
                    new PersonNamePopulator()
                ]
            ),
            new DefaultCacheManagement(new UserKeyFactory())
        );
    }

    public function testConvert(): void
    {
        // Test Fixture
        $source = (new User())->setUuid(17)->setFirstname('Max')->setLastname('Mustermann');
        $time_pre_first_convert = microtime(true);
        // Test Execution
        $target = $this->converter->convert($source);
        $time_post_first_convert = microtime(true);
        // Test Assertion
        self::assertEquals('Max Mustermann', $target->getFullName());
        $time_pre_second_convert = microtime(true);
        $target = $this->converter->convert($source);
        $time_post_second_convert = microtime(true);

        // assert that 2nd conversion is faster because of cache.
        self::assertLessThan(
            $time_post_first_convert - $time_pre_first_convert,
            $time_post_second_convert - $time_pre_second_convert
        );
    }
}
