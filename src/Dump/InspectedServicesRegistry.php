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

    public function addConverter(string $id, ServiceInfo $service): void
    {
        $this->converters[$id] = $service;
    }

    public function addPopulator(string $id, ServiceInfo $service): void
    {
        $this->populators[$id] = $service;
    }

    public function addTargetFactory(string $id, ServiceInfo $service): void
    {
        $this->factories[$id] = $service;
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
