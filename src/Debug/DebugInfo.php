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
        return $type === null ? $this->services : array_filter($this->services, fn($service) => $type === $service->type);
    }
}
