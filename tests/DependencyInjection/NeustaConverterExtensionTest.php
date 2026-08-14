<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayPropertyMappingPopulatorFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Extension-level behaviour that is independent of a concrete converter/populator factory.
 */
class NeustaConverterExtensionTest extends NeustaConverterExtensionTestCase
{
    protected function getConverterFactories(): array
    {
        return [
            new GenericConverterFactory(),
        ];
    }

    protected function getPopulatorFactories(): array
    {
        return [
            new ArrayPropertyMappingPopulatorFactory(),
        ];
    }

    /**
     * `loadInternal()` iterates all four sections unconditionally, so an app that configures none
     * of them must still boot.
     */
    public function test_without_any_configuration(): void
    {
        $this->load([]);

        $this->assertContainerBuilderHasService('neusta_converter.generic_converter');
    }

    /**
     * An app that only uses the deprecated section must not trip over `requiresAtLeastOneElement()`
     * on the new `converters` key.
     */
    public function test_with_deprecated_converter_section_only(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [PersonNamePopulator::class],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar');
    }

    public function test_converter_without_a_type(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Exactly one converter type must be set for a converter.');

        $this->load(['converters' => ['foobar' => []]]);
    }

    public function test_populator_without_a_type(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Exactly one populator type must be set for a populator.');

        $this->load(['populators' => ['foobar' => []]]);
    }

    public function test_populator_with_multiple_types(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Exactly one populator type must be set for a populator.');

        $this->load([
            'populators' => [
                'foobar' => [
                    'property_mapping' => ['target' => 'name'],
                    'array_converting' => ['target' => 'name', 'converter' => 'some.converter'],
                ],
            ],
        ]);
    }

    public function test_property_with_multiple_populator_types(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You cannot set multiple populator types for the same property.');

        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'properties' => [
                            'name' => [
                                'array_converting' => ['converter' => 'some.converter'],
                                'array_property_mapping' => ['source_array_item' => 'value'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Population order is load-bearing: later populators overwrite what earlier ones wrote.
     * Explicitly configured populators run first, then property populators, then context populators.
     */
    public function test_populator_order(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'populators' => [PersonNamePopulator::class],
                        'properties' => [
                            'name' => null,
                            'ageInYears' => 'age',
                        ],
                        'context' => [
                            'locale' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [
            new Reference(PersonNamePopulator::class),
            new Reference('foobar.populator.name'),
            new Reference('foobar.populator.ageInYears'),
            new Reference('foobar.populator.context.locale'),
        ]);
    }
}
