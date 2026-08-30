<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Current;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\BenchContextConfigurator;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Leaf;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\LeafDto;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * ConverterWithDefaultContext with M configurators, flat (no nesting). Shows the per-call cost of
 * building the default Context as a function of the number of configurators. A converter with
 * M=0 configurators has no decorator at all - registered only under "context_configurators".
 *
 * Post-#102 only - ConverterWithDefaultContext doesn't exist before #102.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
#[Bench\ParamProviders('provideConfiguratorCounts')]
final class DefaultContextBench
{
    private Converter $converter;
    private Leaf $source;

    public function setUp(array $params): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $inner = new GenericConverter(
            new GenericTargetFactory(LeafDto::class),
            [
                new PropertyMappingPopulator('id', 'id', accessor: $accessor),
                new PropertyMappingPopulator('name', 'name', accessor: $accessor),
            ],
        );

        $configurators = array_fill(0, $params['configurators'], null);
        $configurators = array_map(static fn () => new BenchContextConfigurator(), $configurators);

        $this->converter = $configurators
            ? new ConverterWithDefaultContext($inner, new ContextFactory($configurators))
            : $inner;
        $this->source = new Leaf(1, 'leaf');
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }

    public function provideConfiguratorCounts(): iterable
    {
        yield 'configurators=0' => ['configurators' => 0];
        yield 'configurators=1' => ['configurators' => 1];
        yield 'configurators=3' => ['configurators' => 3];
    }
}
