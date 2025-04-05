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
        public readonly string $type,
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
            if ('reference' === $arg->type && !\is_array($arg->value)) {
                $refs[] = ltrim((string) $arg->value, '@');

                continue;
            }

            if ('array' === $arg->type && \is_array($arg->value)) {
                foreach ($arg->value as $arrayArg) {
                    if ('reference' === $arrayArg->type && !\is_array($arrayArg->value)) {
                        $refs[] = ltrim((string) $arrayArg->value, '@');
                    }
                }
            }
        }

        return array_unique($refs);
    }
}
