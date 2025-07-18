<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Model;

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
}
