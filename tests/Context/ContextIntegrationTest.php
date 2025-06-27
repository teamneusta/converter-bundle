<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\LanguageContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class ContextIntegrationTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/context.yaml')]
    public function test_global_context_converter(): void
    {
        $person = self::getContainer()->get('global_context_converter')->convert(new User());

        self::assertSame(39, $person->getAge());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/context.yaml')]
    public function test_global_and_parameter_context_converter(): void
    {
        $context = Context::create(new AgeContext(22));

        $person = self::getContainer()->get('global_context_converter')->convert(new User(), $context);

        self::assertSame(22, $person->getAge());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/context.yaml')]
    public function test_global_and_local_context_converter(): void
    {
        $person = self::getContainer()->get('global_and_local_context_converter')->convert(new User());

        self::assertSame(39, $person->getAge());
        self::assertSame('de', $person->getLocale());
    }

    #[ConfigureContainer(__DIR__ . '/../Fixtures/Config/context.yaml')]
    public function test_global_and_local_and_parameter_context_converter(): void
    {
        $context = Context::create(new LanguageContext('en'));

        $person = self::getContainer()->get('global_and_local_context_converter')->convert(new User(), $context);

        self::assertSame(39, $person->getAge());
        self::assertSame('en', $person->getLocale());
    }
}
