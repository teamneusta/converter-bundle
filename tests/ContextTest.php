<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\LanguageContext;
use PHPUnit\Framework\TestCase;

class ContextTest extends TestCase
{
    public function test_has_returns_true_when_the_context_object_is_present(): void
    {
        $ctx = Context::create(new AgeContext(39));

        self::assertTrue($ctx->has(AgeContext::class));
    }

    public function test_has_returns_false_when_the_context_object_is_missing(): void
    {
        $ctx = Context::create(new AgeContext(39));

        self::assertFalse($ctx->has(LanguageContext::class));
    }

    public function test_get_returns_the_context_object(): void
    {
        $ctx = Context::create($age = new AgeContext(39));

        self::assertSame($age, $ctx->get(AgeContext::class));
    }

    public function test_get_throws_when_the_context_object_is_missing(): void
    {
        $ctx = Context::create(new AgeContext(39));

        $this->expectException(\InvalidArgumentException::class);

        $ctx->get(LanguageContext::class);
    }

    public function test_tryGet_returns_the_context_object(): void
    {
        $ctx = Context::create($age = new AgeContext(39));

        self::assertSame($age, $ctx->tryGet(AgeContext::class));
    }

    public function test_tryGet_returns_null_when_the_context_object_is_missing(): void
    {
        $ctx = Context::create(new AgeContext(39));

        self::assertNull($ctx->tryGet(LanguageContext::class));
    }

    public function test_tryGet_allows_falling_back_to_a_default_value(): void
    {
        $ctx = Context::create(new LanguageContext('en'));

        self::assertSame('en', $ctx->tryGet(LanguageContext::class)->language ?? 'de');
        self::assertSame('de', $ctx->without(LanguageContext::class)->tryGet(LanguageContext::class)->language ?? 'de');
    }
}
