<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Current;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\BenchContext;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * The new Context + ContextMappingPopulator($contextClass) path - the main regression candidate
 * found while investigating this suite: it replaces a direct array lookup with tryGet() plus a
 * PropertyAccessor read on the context object, on every conversion that maps a context property.
 *
 * Post-#102 only - Context doesn't exist before #102, so there is no baseline to compare against.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
final class ContextMappingBench
{
    private GenericConverter $converter;
    private Leaf $source;
    private Context $ctx;

    public function setUp(): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $this->converter = new GenericConverter(
            new GenericTargetFactory(LeafDto::class),
            [
                new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                new ContextMappingPopulator('name', 'locale', accessor: $accessor, contextClass: BenchContext::class),
            ],
        );
        $this->source = new Leaf(1, 'unused');
        $this->ctx = Context::create(new BenchContext('de_DE'));
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source, $this->ctx);
    }
}
