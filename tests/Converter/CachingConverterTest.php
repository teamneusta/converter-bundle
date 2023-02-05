<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Cache\InMemoryCache;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\CachingConverter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Tests\Fixtures\Converter\Cache\UserKeyFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;

class CachingConverterTest extends TestCase
{
    /** @var Converter<User, Person> */
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new CachingConverter(
            new GenericConverter(
                new PersonFactory(),
                [
                    new PersonNamePopulator()
                ]
            ),
            new InMemoryCache(new UserKeyFactory())
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
