<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Populator\ArrayConvertingPopulatorFactory;
use Neusta\ConverterBundle\Populator\Mapper\ArrayPropertyMapper;
use Neusta\ConverterBundle\Populator\Mapper\ConverterMapper;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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
                        'source' => 'sourceTest',
                        'target' => 'targetTest',
                        'converter' => GenericConverter::class,
                    ],
                ],
            ],
        ]);

        // populator
        // Todo: extract into base class
        $this->assertContainerBuilderHasService('foobar', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceProperty', 'sourceTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$defaultValue', null);
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument(null);
    }

    public function test_with_array_converting_populator_without_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'target' => 'test',
                        'converter' => GenericConverter::class,
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasService('foobar', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$defaultValue', null);
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument(null);
    }

    public function test_with_array_converting_populator_with_inner_property(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'source' => 'sourceTest',
                        'target' => 'targetTest',
                        'converter' => GenericConverter::class,
                        'source_array_item' => 'value',
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasService('foobar', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceProperty', 'sourceTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$defaultValue', null);
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument('value');
    }

    public function test_with_array_converting_populator_with_inner_property_and_empty_source(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'source' => null,
                        'target' => 'test',
                        'converter' => GenericConverter::class,
                        'source_array_item' => 'value',
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasService('foobar', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$defaultValue', null);
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument('value');
    }

    public function test_with_array_converting_populator_with_inner_property_and_missing_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'target' => 'test',
                        'converter' => GenericConverter::class,
                        'source_array_item' => 'value',
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasService('foobar', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourceProperty', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$skipNull', false);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$defaultValue', null);
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument('value');
    }

    /**
     * `PropertyMappingPopulator` applies the default to the source value *before* the mapper runs,
     * and `ArrayPropertyMapper` turns anything non-array into `[]` - so a default would be produced
     * and immediately discarded. Rejecting it beats writing `[]` and calling it a default.
     */
    public function test_default_value_is_rejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "default" option is not supported for this populator type.');

        $this->load([
            'populators' => [
                'foobar' => [
                    'array_converting' => [
                        'target' => 'contactNumbers',
                        'source' => 'phones',
                        'converter' => GenericConverter::class,
                        'default' => 'fallback',
                    ],
                ],
            ],
        ]);
    }

    private function assertContainerBuilderHasServiceDefinitionWithMapperArgument(?string $sourceArrayItemProperty): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'foobar',
            '$mapper',
            (new Definition(ArrayPropertyMapper::class))->setArguments([
                '$sourceArrayItemProperty' => $sourceArrayItemProperty,
                '$arrayItemAccessor' => null,
                '$mapper' => (new Definition(ConverterMapper::class))->setArguments([
                    '$converter' => new Reference(GenericConverter::class),
                ]),
            ]),
        );
    }
}
