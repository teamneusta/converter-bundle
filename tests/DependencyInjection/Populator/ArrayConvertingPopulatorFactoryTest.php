<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Symfony\Component\DependencyInjection\TypedReference;

class ArrayConvertingPopulatorFactoryTest extends NeustaConverterExtensionTestCase
{
    protected function getPopulatorFactories(): array
    {
        return [
            new ArrayConvertingPopulatorFactory(),
        ];
    }

    public function test_with_array_converting_populator(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'targetTest' => 'sourceTest',
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'sourceTest');
    }

    public function test_with_array_converting_populator_without_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'test' => null,
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'test');
    }

    public function test_with_array_converting_populator_with_inner_property(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'targetTest' => [
                                'source' => 'sourceTest',
                                'source_array_item' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'sourceTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayItemPropertyName', 'value');
    }

    public function test_with_array_converting_populator_with_inner_property_and_empty_source(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'test' => [
                                'source' => null,
                                'source_array_item' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayItemPropertyName', 'value');
    }

    public function test_with_array_converting_populator_with_inner_property_and_missing_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'test' => [
                                'source_array_item' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ArrayConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceArrayItemPropertyName', 'value');
    }
}
