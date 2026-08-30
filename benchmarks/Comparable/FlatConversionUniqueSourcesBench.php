<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Benchmarks\Support\CyclesThroughPool;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Same as FlatConversionBench but with a fresh Leaf per revolution (via CyclesThroughPool) - confirms reusing one source across revs elsewhere in this suite doesn't itself skew results.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
final class FlatConversionUniqueSourcesBench
{
    use CyclesThroughPool;

    private GenericConverter $converter;

    public function setUp(): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $this->converter = new GenericConverter(
            new GenericTargetFactory(LeafDto::class),
            [
                new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                new PropertyMappingPopulator('name', 'name', accessor: $accessor),
            ],
        );

        $this->setPool(array_map(
            static fn (int $i): Leaf => new Leaf($i, "leaf-{$i}"),
            range(0, 999),
        ));
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->nextFromPool());
    }
}
