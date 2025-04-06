<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug;

/**
 * @internal
 */
final class ServiceInfo
{
    /**
     * @param class-string $class
     * @param array<ServiceArgumentInfo> $arguments
     */
    public function __construct(
        public readonly string $class,
        public readonly string $type,
        public readonly array  $arguments,
    )
    {
    }

    /**
     * @return array<string>
     */
    public function getReferences(): array
    {
        $refs = [];

        foreach ($this->arguments as $arg) {
            if ($arg->type === 'reference') {
                $refs[] = ltrim($arg->value, '@');
            }
            if ($arg->type === 'array') {
                foreach ($arg->value as $argArrayValue) {
                    if ($argArrayValue->type === 'reference') {
                        $refs[] = ltrim($argArrayValue->value, '@');
                    }
                }
            }
        }
        return array_unique($refs);
    }
}
