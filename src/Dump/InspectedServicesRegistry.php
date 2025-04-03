<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Dump;

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
    public function allConverters(): array
    {
        return $this->converters;
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function allFactories(): array
    {
        return $this->factories;
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function allPopulators(): array
    {
        return $this->populators;
    }
}
