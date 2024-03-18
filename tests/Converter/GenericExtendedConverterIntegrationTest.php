<?php

declare(strict_types=1);

namespace Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GenericExtendedConverterIntegrationTest extends KernelTestCase
{
    /** @var Converter<User, Person, GenericContext> */
    private Converter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = self::getContainer()->get('test.person.converter.extended');
    }

    public function testConvert_with_null_safety_property(): void
    {
        // Test Fixture
        $source = (new User())
            ->setFullName(null)
            ->setAgeInYears(null);

        // Test Execution
        $target = $this->converter->convert($source);

        // Test Assertion
        self::assertEquals('Hans Herrmann', $target->getFullName());
    }
}
