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
            $this->addRelationsRecursive($chartInfo, $debugInfo, $serviceInfo, $id, $id);
        }

        return $chartInfo;
    }

    private function addRelationsRecursive(
        ChartInfo $chartInfo,
        DebugInfo $debugInfo,
        ServiceInfo $serviceInfo,
        string $nodeID,
        string $id,
    ): void {
        foreach ($serviceInfo->getReferences() as $refId) {
            if (!$refServiceInfo = $debugInfo->service($refId)) {
                continue;
            }

            $chartInfo->addRelation($nodeID, $id, $serviceInfo->type, $refId, $refServiceInfo->type);

            $this->addRelationsRecursive($chartInfo, $debugInfo, $refServiceInfo, $nodeID, $refId);
        }
    }
}
