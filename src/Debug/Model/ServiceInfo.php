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
        return array_unique($this->collectReferences($this->arguments));
    }

    /**
     * References can be nested arbitrarily deep: a converting populator holds its converter inside
     * an inlined mapper definition, which in turn may hold another mapper definition.
     *
     * @param array<ServiceArgumentInfo> $arguments
     *
     * @return array<string>
     */
    private function collectReferences(array $arguments): array
    {
        $refs = [];

        foreach ($arguments as $arg) {
            if ('reference' === $arg->type && \is_string($arg->value)) {
                $refs[] = ltrim($arg->value, '@');
                continue;
            }
            if ('array' === $arg->type && \is_array($arg->value)) {
                $refs = [...$refs, ...$this->collectReferences($arg->value)];
            }
        }

        return $refs;
    }
}
