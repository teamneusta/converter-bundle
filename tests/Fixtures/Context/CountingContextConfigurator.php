<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

/**
 * Counts how often it was called, to assert that the default Context is rebuilt on every
 * convert() call instead of being cached (e.g. as a container singleton).
 */
final class CountingContextConfigurator implements ContextConfigurator
{
    public int $calls = 0;

    public function configureContext(Context $ctx): Context
    {
        return $ctx->with(new AgeContext(++$this->calls));
    }
}
