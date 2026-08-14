<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\Populator\ConditionalPopulator;
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
                        'target' => 'targetTest',
                        'source' => 'sourceTest',
                        'converter' => GenericConverter::class,
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
        $this->assertContainerBuilderHasServiceDefinitionWithMapperArgument();
    }

    /**
     * The deprecated `populator` section supports `condition`, so the section that replaces it has
     * to as well - otherwise conditional populators have no migration path.
     */
    public function test_with_converting_populator_and_condition(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'target' => 'address',
                        'converter' => GenericConverter::class,
                        'condition' => [
                            'property' => 'ageInYears',
                            'expected_value' => 18,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar.conditional', ConditionalPopulator::class);
        self::assertSame('foobar', $this->container->getDefinition('foobar.conditional')->getDecoratedService()[0]);
    }

    /**
     * A populator declared on its own is meant to be fetched and referenced, unlike the ones a
     * converter creates for its own properties.
     */
    public function test_standalone_populator_is_public(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'target' => 'address',
                        'converter' => GenericConverter::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', PropertyMappingPopulator::class);
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
