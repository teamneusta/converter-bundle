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

    /** @var array<string, true> */
    private array $internal = [];

    /**
     * @param bool $internal Marks a service as an implementation detail (e.g. the definition a
     *                       decorator wraps) that is reachable via {@see service()} for graph
     *                       traversal, but excluded from {@see services()}'s top-level listing.
     */
    public function add(string $id, ServiceInfo $service, bool $internal = false): void
    {
        $this->services[$id] = $service;

        if ($internal) {
            $this->internal[$id] = true;
        }
    }

    /**
     * @return array<string, ServiceInfo>
     */
    public function services(?string $type = null): array
    {
        $services = array_diff_key($this->services, $this->internal);

        return null === $type ? $services : array_filter($services, static fn ($service) => $type === $service->type);
    }

    public function service(string $id): ?ServiceInfo
    {
        return $this->services[$id] ?? null;
    }
}
