<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Dump;

/**
 * @internal
 */
final class ServiceInfo
{
    /**
     * @param class-string               $class
     * @param array<ServiceArgumentInfo> $arguments
     */
    public function __construct(
        public readonly string $class,
        public readonly array $arguments,
    ) {
    }
}
