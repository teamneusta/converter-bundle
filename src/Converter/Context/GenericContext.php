<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Context;

use Neusta\ConverterBundle\Context;

class GenericContext
{
    /** @var array<string, mixed> */
    protected array $values = [];

    public function __construct()
    {
        trigger_deprecation(
            'teamneusta/converter-bundle',
            '1.11',
            '"%s" is deprecated, use "%s" instead.',
            self::class,
            Context::class,
        );
    }

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
