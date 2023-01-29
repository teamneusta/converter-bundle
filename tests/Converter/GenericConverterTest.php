<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\Fixtures\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
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
}
