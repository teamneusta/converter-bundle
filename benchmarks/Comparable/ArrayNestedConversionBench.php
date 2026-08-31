<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Node;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\NodeDto;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * ArrayConvertingPopulator over K items, no context - unchanged since #102, so cost stays linear in K, never exponential.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(100)]
#[Bench\Iterations(5)]
#[Bench\ParamProviders('provideCardinalities')]
final class ArrayNestedConversionBench
{
    private Converter $converter;
    private Node $source;

    public function setUp(array $params): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $leafConverter = new GenericConverter(
            new GenericTargetFactory(LeafDto::class),
            [
                new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                new PropertyMappingPopulator('name', 'name', accessor: $accessor),
            ],
        );

        $this->converter = new GenericConverter(
            new GenericTargetFactory(NodeDto::class),
            [
                new PropertyMappingPopulator('value', 'value', accessor: $accessor),
                new ArrayConvertingPopulator($leafConverter, 'items', 'items', accessor: $accessor),
            ],
        );

        $items = [];
        for ($i = 0; $i < $params['k']; ++$i) {
            $items[] = new Leaf($i, "leaf-{$i}");
        }
        $this->source = new Node(0, null, $items);
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }

    public function provideCardinalities(): iterable
    {
        yield 'k=1' => ['k' => 1];
        yield 'k=10' => ['k' => 10];
        yield 'k=100' => ['k' => 100];
    }
}
