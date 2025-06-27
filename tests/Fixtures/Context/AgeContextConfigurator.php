<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

final class AgeContextConfigurator implements ContextConfigurator
{
    public function configureContext(Context $context): Context
    {
        return $context->with(new AgeContext(39));
    }
}
