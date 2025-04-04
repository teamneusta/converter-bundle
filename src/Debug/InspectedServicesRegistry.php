<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug;

/**
 * @internal
 */
final class InspectedServicesRegistry
{
    /** @var array<string, ServiceInfo> */
    private array $converters = [];
    /** @var array<string, ServiceInfo> */
    private array $populators = [];
    /** @var array<string, ServiceInfo> */
    private array $factories = [];

    public function add(string $type, string $id, ServiceInfo $service): void
    {
        match ($type) {
            'converter' => $this->converters[$id] = $service,
            'populator' => $this->populators[$id] = $service,
            'factory' => $this->factories[$id] = $service,
            default => throw new \InvalidArgumentException(\sprintf('Unknown type "%s".', $type)),
        };
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function converters(): array
    {
        return $this->converters;
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function populators(): array
    {
        return $this->populators;
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function factories(): array
    {
        return $this->factories;
    }
}
