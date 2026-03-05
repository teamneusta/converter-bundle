<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

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
}
