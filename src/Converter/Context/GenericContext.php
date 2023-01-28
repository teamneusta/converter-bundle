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

    /**
     * Returns a hash of the values that can be used for building a cache key.
     */
    public function getHash(): string
    {
        return md5(serialize($this->replaceObjectsWithHashes($this->values)));
    }

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    private function replaceObjectsWithHashes(array $array): array
    {
        foreach ($array as $key => $value) {
            $array[$key] = match (true) {
                is_array($value) => $this->replaceObjectsWithHashes($value),
                is_object($value) => spl_object_hash($value),
                default => $value,
            };
        }

        ksort($array);

        return $array;
    }
}
