<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Populator\ConditionalPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Converter\NoopConverterDecorator;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;

class DebugInfoPassTest extends ConfigurableKernelTestCase
{
    #[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/context.yaml')]
    public function test_converter_decorated_by_context_configurators_is_reported_once_under_its_friendly_id(): void
    {
        $debugInfo = self::getContainer()->get(DebugInfo::class);

        $service = $debugInfo->service('global_context_converter');

        self::assertNotNull($service);
        self::assertSame('converter', $service->type);
        self::assertSame(ConverterWithDefaultContext::class, $service->class);

        // the raw, internal decorator id must not show up as a separate entry
        self::assertNull($debugInfo->service('global_context_converter.decorator.context'));

        // but the wrapped GenericConverter's own behavior (factory/populators) must still be
        // reachable - under the id DecoratorServicePass will rename it to - so the debug chart
        // can walk from the decorator into what it actually delegates to
        $inner = $debugInfo->service('global_context_converter.decorator.context.inner');
        self::assertNotNull($inner);
        self::assertSame('converter', $inner->type);
        self::assertSame(GenericConverter::class, $inner->class);

        // the internal linking entry must not leak into the top-level listing either
        self::assertArrayNotHasKey('global_context_converter.decorator.context.inner', $debugInfo->services('converter'));
    }

    #[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/custom_decorator.yaml')]
    public function test_decorator_with_a_custom_renamed_inner_id_is_still_resolved(): void
    {
        $debugInfo = self::getContainer()->get(DebugInfo::class);

        $service = $debugInfo->service('test.person.converter');

        self::assertNotNull($service);
        self::assertSame(NoopConverterDecorator::class, $service->class);

        $inner = $debugInfo->service('test.person.converter.custom_inner_name');
        self::assertNotNull($inner);
        self::assertSame(GenericConverter::class, $inner->class);
    }

    #[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/person_simple.yaml')]
    public function test_converter_without_context_configurators_is_reported_under_its_own_class(): void
    {
        $debugInfo = self::getContainer()->get(DebugInfo::class);

        $service = $debugInfo->service('test.person.converter');

        self::assertNotNull($service);
        self::assertSame('converter', $service->type);
        self::assertSame(GenericConverter::class, $service->class);
    }

    #[ConfigureContainer(__DIR__ . '/../../Fixtures/Config/conditional_property.yaml')]
    public function test_populator_decorated_by_a_condition_is_reported_once_under_its_friendly_id(): void
    {
        // ConditionalPopulator predates the Context system and decorates populators the same way
        // ConverterWithDefaultContext decorates converters, so it hit the same pre-existing bug.
        $debugInfo = self::getContainer()->get(DebugInfo::class);

        $service = $debugInfo->service('conditional_person_address_populator');

        self::assertNotNull($service);
        self::assertSame('populator', $service->type);
        self::assertSame(ConditionalPopulator::class, $service->class);

        self::assertNull($debugInfo->service('conditional_person_address_populator.conditional'));

        $inner = $debugInfo->service('conditional_person_address_populator.conditional.inner');
        self::assertNotNull($inner);
        self::assertSame('populator', $inner->type);
        self::assertSame(ConvertingPopulator::class, $inner->class);
    }
}
