<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

/**
 * Unconditionally adds a BenchContext, even if one is already present via seeding (#111) -
 * the worst case for measuring how many configurators cost.
 */
final class BenchContextConfigurator implements ContextConfigurator
{
    public function configureContext(Context $ctx): Context
    {
        return $ctx->with(new BenchContext('de_DE'));
    }
}
