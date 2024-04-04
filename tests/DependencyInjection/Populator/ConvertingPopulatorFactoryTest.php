<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Symfony\Component\DependencyInjection\TypedReference;

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
                        'converter' => GenericConverter::class,
                        'property' => [
                            'targetTest' => 'sourceTest',
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'targetTest');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourcePropertyName', 'sourceTest');
    }

    public function test_with_converting_populator_without_source_property_config(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'converter' => GenericConverter::class,
                        'property' => [
                            'test' => null,
                        ],
                    ],
                ],
            ],
        ]);

        // populator
        $this->assertContainerBuilderHasPublicService('foobar', ConvertingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$converter', new TypedReference(GenericConverter::class, Converter::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetPropertyName', 'test');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$sourcePropertyName', 'test');
    }
}
