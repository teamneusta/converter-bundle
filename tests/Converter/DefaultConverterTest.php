<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Converter\DefaultConverter;
use Neusta\ConverterBundle\Tests\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Model\Person;
use Neusta\ConverterBundle\Tests\Model\User;
use Neusta\ConverterBundle\Tests\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;

class DefaultConverterTest extends TestCase
{
    /** @var Converter<User,Person> */
    private Converter $converter;

    protected function setUp(): void
    {
        $this->converter = new DefaultConverter(new PersonFactory(), [new PersonNamePopulator()]);
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
        $ctx = new DefaultConverterContext();
        $ctx->setValue('separation char', ', ');
        // Test Execution
        $target = $this->converter->convert($source, $ctx);
        // Test Assertion
        self::assertEquals('Max, Mustermann', $target->getFullName());
    }
}
