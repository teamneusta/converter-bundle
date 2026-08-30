<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Node;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\NodeDto;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A chain of ConvertingPopulators, "depth" levels deep, with no context anywhere in the chain.
 * ConvertingPopulator passes $ctx through unchanged and is byte-identical pre/post #102, so this
 * should stay flat regardless of nesting depth.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
#[Bench\ParamProviders('provideDepths')]
final class NestedConversionBench
{
    private Converter $converter;
    private Node $source;

    public function setUp(array $params): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $this->converter = $this->buildConverter($params['depth'], $accessor);
        $this->source = $this->buildSource($params['depth']);
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }

    public function provideDepths(): iterable
    {
        yield 'depth=1' => ['depth' => 1];
        yield 'depth=3' => ['depth' => 3];
        yield 'depth=5' => ['depth' => 5];
    }

    private function buildConverter(int $depth, PropertyAccessorInterface $accessor): Converter
    {
        $inner = new GenericConverter(
            new GenericTargetFactory(NodeDto::class),
            [new PropertyMappingPopulator('value', 'value', accessor: $accessor)],
        );

        for ($i = 1; $i < $depth; ++$i) {
            $inner = new GenericConverter(
                new GenericTargetFactory(NodeDto::class),
                [
                    new PropertyMappingPopulator('value', 'value', accessor: $accessor),
                    new ConvertingPopulator($inner, 'child', 'child', $accessor),
                ],
            );
        }

        return $inner;
    }

    private function buildSource(int $depth): Node
    {
        $node = new Node($depth);

        for ($i = $depth - 1; $i >= 1; --$i) {
            $node = new Node($i, $node);
        }

        return $node;
    }
}
