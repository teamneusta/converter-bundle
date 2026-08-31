<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Source;

final class Node
{
    /**
     * @param list<Leaf> $items
     */
    public function __construct(
        private int $value,
        private ?self $child = null,
        private array $items = [],
    ) {
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getChild(): ?self
    {
        return $this->child;
    }

    /**
     * @return list<Leaf>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
