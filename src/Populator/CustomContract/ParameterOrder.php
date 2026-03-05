<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;

/**
 * @internal
 */
final class ParameterOrder
{
    /** @param list<'source'|'target'|'context'> $order */
    private function __construct(
        private readonly array $order,
    ) {
    }

    /** @param list<'source'|'target'|'context'> $order */
    public static function fromArray(array $order): self
    {
        return new self($order);
    }

    public static function fromReflection(\ReflectionMethod $method): self
    {
        $order = array_map(self::resolveRole(...), $method->getParameters());

        if (!\in_array('source', $order, true) || !\in_array('target', $order, true)) {
            throw new \LogicException(\sprintf(
                'Method "%s::%s" must have parameters annotated with both #[Source] and #[Target].',
                $method->class,
                $method->name,
            ));
        }

        return new self($order);
    }

    /** @return list<object|null> */
    public function resolveArgs(object $source, object $target, ?object $context): array
    {
        return array_map(fn (string $role) => match ($role) {
            'source' => $source,
            'target' => $target,
            'context' => $context,
        }, $this->order);
    }

    /** @return list<'source'|'target'|'context'> */
    public function toArray(): array
    {
        return $this->order;
    }

    /**
     * @return 'source'|'target'|'context'
     */
    private static function resolveRole(\ReflectionParameter $parameter): string
    {
        return match (true) {
            [] !== $parameter->getAttributes(Source::class) => 'source',
            [] !== $parameter->getAttributes(Target::class) => 'target',
            [] !== $parameter->getAttributes(Context::class) => 'context',
            default => throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s::%s" must be annotated with #[Source], #[Target] or #[Context].',
                $parameter->name,
                $parameter->getDeclaringClass()?->name,
                $parameter->getDeclaringFunction()->name,
            )),
        };
    }
}
