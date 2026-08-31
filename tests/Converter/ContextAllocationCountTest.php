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
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\CountingContextConfigurator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\RecursiveNode;
use PHPUnit\Framework\TestCase;

/**
 * A noise-free complement to the benchmarks/ phpbench suite: instead of timing conversions, this
 * asserts the exact *shape* of context allocation for nesting patterns via ArrayConvertingPopulator
 * and ConvertingPopulator - proving it is linear in array cardinality and in nesting depth, never
 * exponential, and that a converter without context_configurators never creates a Context at all.
 */
class ContextAllocationCountTest extends TestCase
{
    public function test_array_nested_configurator_is_called_once_per_item_not_exponentially(): void
    {
        $counting = new CountingContextConfigurator();

        $itemConverter = new ConverterWithDefaultContext(
            new GenericConverter(new GenericTargetFactory(RecursiveNode::class), []),
            new ContextFactory([$counting]),
        );

        $outer = new GenericConverter(
            new GenericTargetFactory(RecursiveNode::class),
            [new ArrayConvertingPopulator($itemConverter, 'items', 'items')],
        );

        $source = new RecursiveNode();
        $source->items = array_fill(0, 10, new RecursiveNode());

        $outer->convert($source);

        self::assertSame(10, $counting->calls);
    }

    public function test_configurator_at_every_nesting_level_is_called_once_per_level_not_exponentially(): void
    {
        $counting = new CountingContextConfigurator();

        $buildLevel = static fn (?Converter $inner = null): Converter => new ConverterWithDefaultContext(
            new GenericConverter(
                new GenericTargetFactory(RecursiveNode::class),
                null !== $inner ? [new ConvertingPopulator($inner, 'child', 'child')] : [],
            ),
            new ContextFactory([$counting]),
        );

        $level3 = $buildLevel($buildLevel($buildLevel()));

        $source = new RecursiveNode();
        $source->child = new RecursiveNode();
        $source->child->child = new RecursiveNode();

        $level3->convert($source);

        self::assertSame(3, $counting->calls);
    }

    public function test_converter_without_context_configurators_never_creates_a_context_however_deeply_nested(): void
    {
        $capturedCtx = 'not-yet-populated';
        $capturingPopulator = new class($capturedCtx) implements Populator {
            public function __construct(private mixed &$capturedCtx)
            {
            }

            public function populate(object $target, object $source, ?object $ctx = null): void
            {
                $this->capturedCtx = $ctx;
            }
        };

        $inner = new GenericConverter(new GenericTargetFactory(RecursiveNode::class), [$capturingPopulator]);
        for ($i = 0; $i < 4; ++$i) {
            $inner = new GenericConverter(
                new GenericTargetFactory(RecursiveNode::class),
                [new ConvertingPopulator($inner, 'child', 'child')],
            );
        }

        $source = new RecursiveNode();
        $node = $source;
        for ($i = 0; $i < 5; ++$i) {
            $node->child = new RecursiveNode();
            $node = $node->child;
        }

        $inner->convert($source);

        self::assertNull($capturedCtx);
    }

    public function test_defensive_configurator_under_seeding_reuses_the_seeded_instance(): void
    {
        $seededAge = new AgeContext(42);
        $defensiveConfigurator = new class implements ContextConfigurator {
            public int $calls = 0;

            public function configureContext(Context $ctx): Context
            {
                if ($ctx->has(AgeContext::class)) {
                    return $ctx;
                }

                ++$this->calls;

                return $ctx->with(new AgeContext(1));
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

        $converter = new ConverterWithDefaultContext(
            new GenericConverter(new GenericTargetFactory(\stdClass::class), [$capturingPopulator]),
            new ContextFactory([$defensiveConfigurator]),
        );

        $converter->convert(new \stdClass(), Context::create($seededAge));

        self::assertSame(0, $defensiveConfigurator->calls);
        self::assertInstanceOf(Context::class, $capturedCtx);
        self::assertSame($seededAge, $capturedCtx->get(AgeContext::class));
    }
}
