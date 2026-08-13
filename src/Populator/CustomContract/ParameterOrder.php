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
    /** @var array<'source'|'target'|'context', class-string> */
    private const ROLE_ATTRIBUTES = [
        'source' => Source::class,
        'target' => Target::class,
        'context' => Context::class,
    ];

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
        return array_map(static fn (string $role) => match ($role) {
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
        $roles = [];
        foreach (self::ROLE_ATTRIBUTES as $role => $attribute) {
            if ($count = \count($parameter->getAttributes($attribute))) {
                array_push($roles, ...array_fill(0, $count, $role));
            }
        }

        if ([] === $roles) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" must be annotated with #[Source], #[Target] or #[Context].',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
            ));
        }

        if (1 < \count($roles)) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" must be annotated with exactly one of #[Source], #[Target] or #[Context], got: %s.',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
                implode(', ', $roles),
            ));
        }

        if ('context' === $roles[0] && !$parameter->allowsNull()) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" annotated with #[Context] must be nullable.',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
            ));
        }

        return $roles[0];
    }

    /**
     * @param list<mixed> $order
     */
    private static function validateOrder(array $order, string $subject): void
    {
        foreach ($order as $index => $role) {
            if (!\in_array($role, array_keys(self::ROLE_ATTRIBUTES), true)) {
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

    private static function describeDeclaringMethod(\ReflectionParameter $parameter): string
    {
        return \sprintf(
            '%s::%s',
            $parameter->getDeclaringClass()?->name,
            $parameter->getDeclaringFunction()->name,
        );
    }
}
