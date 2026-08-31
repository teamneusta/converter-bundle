<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

/**
 * Skips rebuilding BenchContext if a seeded/outer Context already has one - the intended use of #111's seeding.
 */
final class DefensiveBenchContextConfigurator implements ContextConfigurator
{
    public function configureContext(Context $ctx): Context
    {
        return $ctx->has(BenchContext::class) ? $ctx : $ctx->with(new BenchContext('de_DE'));
    }
}
