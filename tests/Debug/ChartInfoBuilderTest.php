<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Debug;

use Neusta\ConverterBundle\Debug\Builder\ChartInfoBuilder;
use Neusta\ConverterBundle\Debug\Model\ChartInfo;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceInfo;
use PHPUnit\Framework\TestCase;

final class ChartInfoBuilderTest extends TestCase
{
    public function testSimpleRelationBetweenServices(): void
    {
        $serviceA = new ServiceInfo('type_a', 'MyServiceA', [
            new ServiceArgumentInfo('reference', '@service_b'),
        ]);
        $serviceB = new ServiceInfo('type_b', 'MyServiceB', []);

        $debugInfo = new DebugInfo();
        $debugInfo->add('service_a', $serviceA);
        $debugInfo->add('service_b', $serviceB);

        $builder = new ChartInfoBuilder();
        $chartInfo = $builder->buildFromDebugInfo($debugInfo);

        $this->assertInstanceOf(ChartInfo::class, $chartInfo);
        $this->assertCount(1, $chartInfo->nodes);
    }

    public function testComplexeRelationBetweenServices(): void
    {
        $serviceA = new ServiceInfo('type_a', 'MyServiceA', [
            new ServiceArgumentInfo('reference', '@service_b'),
        ]);
        $serviceB = new ServiceInfo('type_b', 'MyServiceB', [
            new ServiceArgumentInfo('reference', '@service_c'),
        ]);
        $serviceC = new ServiceInfo('type_c', 'MyServiceC', []);

        $debugInfo = new DebugInfo();
        $debugInfo->add('service_a', $serviceA);
        $debugInfo->add('service_b', $serviceB);
        $debugInfo->add('service_c', $serviceC);

        $builder = new ChartInfoBuilder();
        $chartInfo = $builder->buildFromDebugInfo($debugInfo);

        $this->assertInstanceOf(ChartInfo::class, $chartInfo);
        $this->assertCount(2, $chartInfo->nodes);
        $this->assertCount(2, $chartInfo->nodes['service_a']);
        $this->assertCount(1, $chartInfo->nodes['service_b']);
    }
}
