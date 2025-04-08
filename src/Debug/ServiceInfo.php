<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug;

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
            if ('reference' === $arg->type) {
                $refs[] = ltrim($arg->value, '@');

                continue;
            }

            if ('array' === $arg->type) {
                foreach ($arg->value as $argArrayValue) {
                    if ('reference' === $argArrayValue->type) {
                        $refs[] = ltrim($argArrayValue->value, '@');
                    }
                }
            }
        }

        return array_unique($refs);
    }
}
