<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Context;

final class BenchContext
{
    public function __construct(
        private string $locale,
    ) {
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
