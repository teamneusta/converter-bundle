<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\OpaqueContext;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Passes an arbitrary object (neither Context nor GenericContext) as $ctx. Post-#102,
 * GenericConverter::convert() fires trigger_deprecation() on every such call - isolated here so
 * it can't smear its cost onto the other scenarios in this suite.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
final class CustomContextObjectBench
{
    private GenericConverter $converter;
    private Leaf $source;
    private OpaqueContext $ctx;

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
        $this->ctx = new OpaqueContext();
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source, $this->ctx);
    }
}
