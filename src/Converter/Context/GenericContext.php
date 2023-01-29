<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Context;

class GenericContext
{
    /** @var array<string, mixed> */
    protected array $values;

    public function hasKey(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    public function getValue(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    /**
     * @return $this
     */
    public function setValue(string $key, mixed $value): static
    {
        $this->values[$key] = $value;

        return $this;
    }
}
