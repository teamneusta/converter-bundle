<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Populator\ConditionalPopulator;
use Neusta\ConverterBundle\Tests\ConfigurableKernelTestCase;
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
        self::assertNull($debugInfo->service('global_context_converter.decorator.context.inner'));
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
    }
}
