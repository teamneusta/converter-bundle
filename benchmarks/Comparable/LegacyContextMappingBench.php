<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Comparable;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * The legacy GenericContext + ContextMappingPopulator path, which exists on both sides of #102.
 * Post-#102 ContextMappingPopulator::populate() allocates an extra $readValue closure per call
 * that didn't exist before - isolating whether that shows up in wall-clock terms is the point.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
final class LegacyContextMappingBench
{
    private GenericConverter $converter;
    private Leaf $source;
    private GenericContext $ctx;

    public function setUp(): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $this->converter = new GenericConverter(
            new GenericTargetFactory(LeafDto::class),
            [
                new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                new ContextMappingPopulator('name', 'name', accessor: $accessor),
            ],
        );
        $this->source = new Leaf(1, 'unused');
        // GenericContext::__construct() triggers a deprecation post-#102 - built once here so
        // setUp(), not the benched convert() call, pays for it.
        $this->ctx = (new GenericContext())->setValue('name', 'from-context');
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source, $this->ctx);
    }
}
