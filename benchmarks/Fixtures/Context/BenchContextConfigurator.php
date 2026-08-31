<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

/**
 * Unconditionally adds a BenchContext, even under seeding (#111) - the worst case for measuring configurator cost.
 */
final class BenchContextConfigurator implements ContextConfigurator
{
    public function configureContext(Context $ctx): Context
    {
        return $ctx->with(new BenchContext('de_DE'));
    }
}
