<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\Mapper;

final class UppercaseMapper
{
    public function __construct(
        private readonly bool $trim = false,
    ) {
    }

    public function __invoke(mixed $value, ?object $ctx = null): mixed
    {
        if (!\is_string($value)) {
            return $value;
        }

        return strtoupper($this->trim ? trim($value) : $value);
    }
}
