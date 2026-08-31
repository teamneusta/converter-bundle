<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Target;

final class NodeDto
{
    private int $value;
    private ?self $child = null;
    /** @var list<LeafDto> */
    private array $items = [];

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setChild(?self $child): void
    {
        $this->child = $child;
    }

    public function getChild(): ?self
    {
        return $this->child;
    }

    /**
     * @param list<LeafDto> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return list<LeafDto>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
