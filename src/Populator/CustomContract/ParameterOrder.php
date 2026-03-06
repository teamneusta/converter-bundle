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
        self::validateOrder($order, 'Parameter order array');

        return new self($order);
    }

    public static function fromReflection(\ReflectionMethod $method): self
    {
        $order = array_map(self::resolveRole(...), $method->getParameters());

        self::validateOrder($order, \sprintf('Method "%s::%s"', $method->class, $method->name));

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
        if ([] !== $parameter->getAttributes(Source::class)) {
            return 'source';
        }

        if ([] !== $parameter->getAttributes(Target::class)) {
            return 'target';
        }

        if ([] !== $parameter->getAttributes(Context::class)) {
            if (!$parameter->allowsNull()) {
                throw new \LogicException(\sprintf(
                    'Parameter "$%s" of method "%s::%s" annotated with #[Context] must be nullable.',
                    $parameter->name,
                    $parameter->getDeclaringClass()?->name,
                    $parameter->getDeclaringFunction()->name,
                ));
            }

            return 'context';
        }

        throw new \LogicException(\sprintf(
            'Parameter "$%s" of method "%s::%s" must be annotated with #[Source], #[Target] or #[Context].',
            $parameter->name,
            $parameter->getDeclaringClass()?->name,
            $parameter->getDeclaringFunction()->name,
        ));
    }

    /**
     * @param list<mixed> $order
     */
    private static function validateOrder(array $order, string $subject): void
    {
        foreach ($order as $index => $role) {
            if (!\in_array($role, ['source', 'target', 'context'], true)) {
                throw new \LogicException(\sprintf(
                    '%s contains invalid role "%s" at index %d.',
                    $subject,
                    \is_scalar($role) ? $role : get_debug_type($role),
                    $index,
                ));
            }
        }

        $roleCounts = array_count_values($order);

        if (1 !== ($roleCounts['source'] ?? 0) || 1 !== ($roleCounts['target'] ?? 0)) {
            throw new \LogicException(\sprintf(
                '%s must contain exactly one "source" role and exactly one "target" role.',
                $subject,
            ));
        }

        if (($roleCounts['context'] ?? 0) > 1) {
            throw new \LogicException(\sprintf(
                '%s must not contain more than one "context" role.',
                $subject,
            ));
        }
    }
}
