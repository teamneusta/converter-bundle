<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Current;

use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\BenchContextConfigurator;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Context\DefensiveBenchContextConfigurator;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Source\Node;
use Neusta\ConverterBundle\Benchmarks\Fixtures\Target\NodeDto;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PhpBench\Attributes as Bench;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Nested decorated converters where the outer's Context seeds the inner's ContextFactory::create() (#111) - defensive vs. unconditional configurator. The wall-clock delta tends to sit within
 * noise; see ContextAllocationCountTest for the deterministic proof of the seeding benefit.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
#[Bench\ParamProviders('provideConfiguratorStyles')]
final class SeedingBenefitBench
{
    private Converter $converter;
    private Node $source;

    public function setUp(array $params): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $configuratorClass = $params['configurator'];

        $inner = new ConverterWithDefaultContext(
            new GenericConverter(
                new GenericTargetFactory(NodeDto::class),
                [new PropertyMappingPopulator('value', 'value', accessor: $accessor)],
            ),
            new ContextFactory([new $configuratorClass()]),
        );

        $this->converter = new ConverterWithDefaultContext(
            new GenericConverter(
                new GenericTargetFactory(NodeDto::class),
                [
                    new PropertyMappingPopulator('value', 'value', accessor: $accessor),
                    new ConvertingPopulator($inner, 'child', 'child', $accessor),
                ],
            ),
            new ContextFactory([new $configuratorClass()]),
        );

        $this->source = new Node(1, new Node(2));
    }

    public function benchConvert(): void
    {
        $this->converter->convert($this->source);
    }

    public function provideConfiguratorStyles(): iterable
    {
        yield 'unconditional' => ['configurator' => BenchContextConfigurator::class];
        yield 'defensive' => ['configurator' => DefensiveBenchContextConfigurator::class];
    }
}
