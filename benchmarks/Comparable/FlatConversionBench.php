<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * A flat GenericConverter with no context at all - the noise floor every other scenario here is measured against.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
final class FlatConversionBench
{
    private GenericConverter $converter;
    private Leaf $source;

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
        $this->source = new Leaf(1, 'leaf');
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }
}
