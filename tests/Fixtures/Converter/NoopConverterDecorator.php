<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Converter;

use Neusta\ConverterBundle\Converter;

final class NoopConverterDecorator implements Converter
{
    public function __construct(
        private readonly Converter $decorated,
    ) {
    }

    public function convert(object $source, ?object $ctx = null): object
    {
        return $this->decorated->convert($source, $ctx);
    }
}
