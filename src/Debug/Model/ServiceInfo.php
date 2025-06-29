<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Model;

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
        public readonly string $type,
        public readonly string $class,
        public readonly array $arguments,
    ) {
    }

    /**
     * @return array<string>
     */
    public function getReferences(): array
    {
        $refs = [];

        foreach ($this->arguments as $arg) {
            if ('reference' === $arg->type && \is_string($arg->value)) {
                $refs[] = ltrim($arg->value, '@');
                continue;
            }
            if ('array' === $arg->type && \is_array($arg->value)) {
                foreach ($arg->value as $argArrayValue) {
                    if ('reference' === $argArrayValue->type && \is_string($argArrayValue->value)) {
                        $refs[] = ltrim($argArrayValue->value, '@');
                    }
                }
            }
        }

        return array_unique($refs);
    }
}
