<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use PHPUnit\Framework\TestCase;

class ContextFactoryTest extends TestCase
{
    public function test_create_without_seed_starts_from_an_empty_context(): void
    {
        $configurator = new class implements ContextConfigurator {
            public function configureContext(Context $ctx): Context
            {
                return $ctx->with(new AgeContext(39));
            }
        };

        $context = (new ContextFactory([$configurator]))->create();

        self::assertSame(39, $context->get(AgeContext::class)->age);
    }

    public function test_create_seeds_configurators_with_the_given_context(): void
    {
        $configurator = new class implements ContextConfigurator {
            public ?bool $sawSeed = null;

            public function configureContext(Context $ctx): Context
            {
                $this->sawSeed = $ctx->has(AgeContext::class);

                return $ctx;
            }
        };

        (new ContextFactory([$configurator]))->create(Context::create(new AgeContext(22)));

        self::assertTrue($configurator->sawSeed);
    }

    public function test_create_lets_a_configurator_skip_work_via_the_seed(): void
    {
        $configurator = new class implements ContextConfigurator {
            public int $calls = 0;

            public function configureContext(Context $ctx): Context
            {
                if ($ctx->has(AgeContext::class)) {
                    return $ctx;
                }

                ++$this->calls;

                return $ctx->with(new AgeContext(39));
            }
        };

        $context = (new ContextFactory([$configurator]))->create(Context::create(new AgeContext(22)));

        self::assertSame(0, $configurator->calls);
        self::assertSame(22, $context->get(AgeContext::class)->age);
    }
}
