<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Current;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\BenchContextConfigurator;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Node;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\NodeDto;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Each level (Node and array-nested Leaf) carries its own ConverterWithDefaultContext (M=1, unconditional) - varying depth and k independently shows the cost is K x (M+1) clones, linear in both, never exponential.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(100)]
#[Bench\Iterations(5)]
#[Bench\ParamProviders('provideShapes')]
final class DefaultContextNestedBench
{
    private Converter $converter;
    private Node $source;

    public function setUp(array $params): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $leafConverter = $this->decorate(
            new GenericConverter(
                new GenericTargetFactory(LeafDto::class),
                [
                    new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                    new PropertyMappingPopulator('name', 'name', accessor: $accessor),
                ],
            ),
        );

        $inner = $this->decorate(
            new GenericConverter(
                new GenericTargetFactory(NodeDto::class),
                [
                    new PropertyMappingPopulator('value', 'value', accessor: $accessor),
                    new ArrayConvertingPopulator($leafConverter, 'items', 'items', accessor: $accessor),
                ],
            ),
        );

        for ($i = 1; $i < $params['depth']; ++$i) {
            $inner = $this->decorate(
                new GenericConverter(
                    new GenericTargetFactory(NodeDto::class),
                    [
                        new PropertyMappingPopulator('value', 'value', accessor: $accessor),
                        new ConvertingPopulator($inner, 'child', 'child', $accessor),
                    ],
                ),
            );
        }

        $this->converter = $inner;

        $items = [];
        for ($i = 0; $i < $params['k']; ++$i) {
            $items[] = new Leaf($i, "leaf-{$i}");
        }

        $node = new Node($params['depth'], null, $items);

        for ($i = $params['depth'] - 1; $i >= 1; --$i) {
            $node = new Node($i, $node);
        }
        $this->source = $node;
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }

    public function provideShapes(): iterable
    {
        yield 'depth=1,k=1' => ['depth' => 1, 'k' => 1];
        yield 'depth=1,k=100' => ['depth' => 1, 'k' => 100];
        yield 'depth=3,k=1' => ['depth' => 3, 'k' => 1];
        yield 'depth=3,k=100' => ['depth' => 3, 'k' => 100];
    }

    private function decorate(Converter $inner): Converter
    {
        return new ConverterWithDefaultContext($inner, new ContextFactory([new BenchContextConfigurator()]));
    }
}
