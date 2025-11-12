<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle;

final class Context
{
    /**
     * @param array<class-string, object> $context
     */
    private function __construct(
        private array $context = [],
    ) {}

    public static function create(object ...$objects): self
    {
        $context = [];
        foreach ($objects as $object) {
            $context[$object::class] = $object;
        }

        return new self($context);
    }

    public function with(object ...$objects): self
    {
        $clone = clone $this;

        foreach ($objects as $object) {
            if ($object instanceof self) {
                $clone->context = array_merge($clone->context, $object->context);
            } else {
                $clone->context[$object::class] = $object;
            }
        }

        return $clone;
    }

    /**
     * @param object|class-string $value
     */
    public function without(object|string $value): self
    {
        if ($value instanceof self) {
            throw new \InvalidArgumentException('Cannot remove context from another context.');
        }

        $class = \is_string($value) ? $value : $value::class;

        if (!isset($this->context[$class])) {
            return $this;
        }

        $clone = clone $this;
        unset($clone->context[$class]);

        return $clone;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function get(string $class): ?object
    {
        // @phpstan-ignore-next-line return.type
        return $this->context[$class] ?? null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    public function __invoke(string $class): ?object
    {
        // @phpstan-ignore-next-line return.type
        return $this->context[$class] ?? null;
    }
}
