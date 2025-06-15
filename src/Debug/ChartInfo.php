<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug;

/**
 * @internal
 */
final class ChartInfo
{
    /** @var array<string, list<string>> */
    public array $nodes = [];

    public function addRelation(
        string $nodeId,
        string $sourceId,
        string $sourceType,
        string $targetId,
        string $targetType,
    ): void {
        $this->nodes[$nodeId][] = "{$sourceId}:::{$sourceType} --> {$targetId}:::{$targetType}";
    }
}
