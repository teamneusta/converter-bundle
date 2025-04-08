<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Builder;

use Neusta\ConverterBundle\Debug\ChartInfo;
use Neusta\ConverterBundle\Debug\DebugInfo;
use Neusta\ConverterBundle\Debug\ServiceInfo;

/**
 * @internal
 */
final class ChartInfoBuilder
{
    public function buildFromDebugInfo(DebugInfo $debugInfo): ChartInfo
    {
        $chartInfo = new ChartInfo();

        foreach ($debugInfo->services() as $id => $serviceInfo) {
            $chartInfo->addNode($id);
            $this->addRelationsRecursive($chartInfo, $debugInfo, $id, $id, $serviceInfo);
        }

        return $chartInfo;
    }

    private function addRelationsRecursive(ChartInfo $chartInfo, DebugInfo $debugInfo, string $nodeID, string $id, ?ServiceInfo $serviceInfo): void
    {
        $type = $serviceInfo?->type ?? 'unknown';

        foreach ($serviceInfo?->getReferences() ?? [] as $refId) {
            $chartInfo->addRelation($nodeID, $id, $type, $refId, $debugInfo->service($refId)?->type ?? 'unknown');
            $this->addRelationsRecursive($chartInfo, $debugInfo, $nodeID, $refId, $debugInfo->service($refId));
        }
    }
}
