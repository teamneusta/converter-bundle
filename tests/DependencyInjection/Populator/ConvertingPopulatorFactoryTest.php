<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\Populator\Mapper\ConverterMapper;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConvertingPopulatorFactoryTest extends NeustaConverterExtensionTestCase
{
    protected function getPopulatorFactories(): array
    {
        return [
            new ConvertingPopulatorFactory(),
        ];
    }

    public function test_with_converting_populator(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'source' => 'sourceTest',
                        'target' => 'targetTest',
                        'converting' => [
                            'converter' => GenericConverter::class,
                        ],
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
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument();
    }

    public function test_with_converting_populator_without_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'target' => 'test',
                        'converting' => [
                            'converter' => GenericConverter::class,
                        ],
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
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument();
    }

    private function assertContainerBuilderHasServiceDefinitionWithMapperArgument(): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'foobar',
            '$mapper',
            (new Definition(ConverterMapper::class))->setArguments([
                '$converter' => new Reference(GenericConverter::class),
            ]),
        );
    }
}
