<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\LanguageContext;
use PHPUnit\Framework\TestCase;

/**
 * Proves that a context_configurators-decorated converter nested inside another one (e.g. via
 * ArrayConvertingPopulator) is seeded with the already-resolved Context, so a defensive
 * configurator can skip work that the outer converter's value would override anyway - while a
 * context class only the inner converter's own configurator produces still reaches it.
 */
class NestedContextConfiguratorsTest extends TestCase
{
    public function test_defensive_inner_configurator_skips_overlap_but_still_contributes_its_own_class(): void
    {
        $outerConfigurator = new class implements ContextConfigurator {
            public function configureContext(Context $ctx): Context
            {
                return $ctx->with(new AgeContext(100));
            }
        };
        $innerConfigurator = new class implements ContextConfigurator {
            public int $ageCalls = 0;

            public function configureContext(Context $ctx): Context
            {
                if (!$ctx->has(AgeContext::class)) {
                    ++$this->ageCalls;
                    $ctx = $ctx->with(new AgeContext(1));
                }

                // LanguageContext is local to this converter - no ancestor provides it, so it must
                // survive the safety-net merge and actually reach the innermost conversion.
                return $ctx->with(new LanguageContext('en'));
            }
        };

        $capturedCtx = null;
        $capturingPopulator = new class($capturedCtx) implements Populator {
            public function __construct(private mixed &$capturedCtx)
            {
            }

            public function populate(object $target, object $source, ?object $ctx = null): void
            {
                $this->capturedCtx = $ctx;
            }
        };

        $inner = new ConverterWithDefaultContext(
            new GenericConverter(new GenericTargetFactory(\stdClass::class), [$capturingPopulator]),
            new ContextFactory([$innerConfigurator]),
        );

        $nestingPopulator = new class($inner) implements Populator {
            public function __construct(private Converter $inner)
            {
            }

            public function populate(object $target, object $source, ?object $ctx = null): void
            {
                $this->inner->convert($source, $ctx);
            }
        };

        $outer = new ConverterWithDefaultContext(
            new GenericConverter(new GenericTargetFactory(\stdClass::class), [$nestingPopulator]),
            new ContextFactory([$outerConfigurator]),
        );

        $outer->convert(new \stdClass());

        self::assertInstanceOf(Context::class, $capturedCtx);
        // the overlapping class: outer's value wins, inner's configurator skipped producing its own
        self::assertSame(100, $capturedCtx->get(AgeContext::class)->age);
        self::assertSame(0, $innerConfigurator->ageCalls);
        // the local-only class: inner's configurator still contributes it, unaffected by seeding
        self::assertSame('en', $capturedCtx->get(LanguageContext::class)->language);
    }
}
