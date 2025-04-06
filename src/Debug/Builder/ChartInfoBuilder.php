<?php

namespace Neusta\ConverterBundle\Debug\Builder;

use Neusta\ConverterBundle\Debug\ChartInfo;
use Neusta\ConverterBundle\Debug\DebugInfo;
use Neusta\ConverterBundle\Debug\ServiceInfo;

class ChartInfoBuilder
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
        $refs = $serviceInfo?->getReferences() ?? [];

        foreach ($refs as $refId) {
            $chartInfo->addRelation($nodeID, $id, $type, $refId, $debugInfo->serviceById($refId)?->type ?? 'unknown');
            $this->addRelationsRecursive($chartInfo, $debugInfo, $nodeID, $refId, $debugInfo->serviceById($refId));
        }
    }
}
