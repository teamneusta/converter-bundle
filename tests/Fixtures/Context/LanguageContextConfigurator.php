<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;

final class LanguageContextConfigurator implements ContextConfigurator
{
    public function configureContext(Context $ctx): Context
    {
        return $ctx->with(new LanguageContext('de'));
    }
}
