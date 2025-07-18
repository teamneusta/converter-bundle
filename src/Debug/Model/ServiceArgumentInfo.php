<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Model;

/**
 * @internal
 */
final class ServiceArgumentInfo
{
    /**
     * @param scalar|array<self> $value
     */
    public function __construct(
        public readonly string $type,
        public readonly int|float|string|bool|array $value,
    ) {
    }
}
