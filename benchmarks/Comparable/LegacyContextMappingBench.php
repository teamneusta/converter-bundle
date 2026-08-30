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
 * The legacy GenericContext + ContextMappingPopulator path (exists pre- and post-#102) - regression guard for the removed per-call closure allocation in ContextMappingPopulator::populate().
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
        // GenericContext::__construct() triggers a deprecation - built once here so setUp() pays for it, not benchConvert().
        $this->ctx = (new GenericContext())->setValue('name', 'from-context');
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source, $this->ctx);
    }
}
