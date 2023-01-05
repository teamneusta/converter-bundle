<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Tests\BundleKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;

class DefaultCachedConverterIntegrationTest extends BundleKernelTestCase
{
    /** @var Converter<User,Person> */
    private Converter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = $this->getContainer()->get('test.person.converter.with.cache');
    }

    public function testConvert(): void
    {
        // Test Fixture
        $source = (new User())->setUuid(17)->setFirstname('Max')->setLastname('Mustermann');
        // Test Execution
        $target = $this->converter->convert($source);
        // Test Assertion
        self::assertEquals('Max Mustermann', $target->getFullName());
    }

    public function testConvertWithContext(): void
    {
        // Test Fixture
        $source = (new User())->setUuid(17)->setFirstname('Max')->setLastname('Mustermann');
        $ctx = new DefaultConverterContext();
        $ctx->setValue('separation char', ', ');
        // Test Execution
        $target = $this->converter->convert($source, $ctx);
        // Test Assertion
        self::assertEquals('Max, Mustermann', $target->getFullName());
    }
}
