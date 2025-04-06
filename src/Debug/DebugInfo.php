<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug;

/**
 * @internal
 */
final class DebugInfo
{
    /** @var array<string, ServiceInfo> */
    private array $services = [];

    public function add(string $id, ServiceInfo $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function services(?string $type = null): array
    {
        return null === $type ? $this->services : array_filter($this->services, fn ($service) => $type === $service->type);
    }

    public function service(string $id): ?ServiceInfo
    {
        return $this->services[$id] ?? null;
    }

    public function serviceById(string $id): ?ServiceInfo
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }
        return null;
    }
}
