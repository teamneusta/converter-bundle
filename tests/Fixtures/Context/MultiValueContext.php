<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

final class MultiValueContext
{
    public function __construct(
        public readonly string $foo,
        public readonly string $bar,
    ) {
    }
}
