<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Context;

final class AgeContext
{
    public function __construct(
        public readonly int $age,
    ) {
    }
}
