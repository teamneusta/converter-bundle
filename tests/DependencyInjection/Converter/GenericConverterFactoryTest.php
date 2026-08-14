<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use Symfony\Component\DependencyInjection\Reference;

class GenericConverterFactoryTest extends NeustaConverterExtensionTestCase
{
    protected function getConverterFactories(): array
    {
        return [
            new GenericConverterFactory(),
        ];
    }

    public function test_with_generic_converter(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasAlias(Converter::class . ' $foobarConverter', 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference(PersonFactory::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [new Reference(PersonNamePopulator::class)]);
    }

    public function test_with_mapped_properties(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'properties' => [
                            'name' => null,
                            'ageInYears' => 'age',
                            'email' => [
                                'source' => 'mail',
                            ],
                            'fullName?' => null,
                        ],
                    ],
                ],
            ],
        ]);

        // converter
        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasAlias(Converter::class . ' $foobarConverter', 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference(PersonFactory::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [
            new Reference('foobar.populator.name'),
            new Reference('foobar.populator.ageInYears'),
            new Reference('foobar.populator.email'),
            new Reference('foobar.populator.fullName'),
        ]);

        // name property populator
        $this->assertContainerBuilderHasService('foobar.populator.name', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$targetProperty', 'name');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$sourceProperty', 'name');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$skipNull', false);

        // ageInYears property populator
        $this->assertContainerBuilderHasService('foobar.populator.ageInYears', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$targetProperty', 'ageInYears');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$sourceProperty', 'age');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$skipNull', false);

        // email property populator
        $this->assertContainerBuilderHasService('foobar.populator.email', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.email', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.email', '$targetProperty', 'email');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.email', '$sourceProperty', 'mail');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.email', '$skipNull', false);

        // fullName property populator
        $this->assertContainerBuilderHasService('foobar.populator.fullName', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.fullName', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.fullName', '$targetProperty', 'fullName');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.fullName', '$sourceProperty', 'fullName');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.fullName', '$skipNull', true);
    }

    public function test_with_mapped_context(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'name' => null,
                            'ageInYears' => 'age',
                        ],
                    ],
                ],
            ],
        ]);

        // converter
        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasAlias(Converter::class . ' $foobarConverter', 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference(PersonFactory::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [
            new Reference('foobar.populator.context.name'),
            new Reference('foobar.populator.context.ageInYears'),
        ]);

        // name context populator
        $this->assertContainerBuilderHasService('foobar.populator.context.name', ContextMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.name', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.name', '$targetProperty', 'name');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.name', '$contextProperty', 'name');

        // ageInYears context populator
        $this->assertContainerBuilderHasService('foobar.populator.context.ageInYears', ContextMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$targetProperty', 'ageInYears');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextProperty', 'age');
    }

    public function test_with_array_converting_populator_with_default_value(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'properties' => [
                            'name' => [
                                'source' => null,
                                'default' => 'John Doe',
                            ],
                            'ageInYears' => [
                                'source' => 'age',
                                'default' => 42,
                            ],
                            'locale' => [
                                'default' => 'en',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // name property populator
        $this->assertContainerBuilderHasService('foobar.populator.name', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$targetProperty', 'name');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$sourceProperty', 'name');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.name', '$defaultValue', 'John Doe');

        // ageInYears property populator
        $this->assertContainerBuilderHasService('foobar.populator.ageInYears', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$targetProperty', 'ageInYears');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$sourceProperty', 'age');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$defaultValue', 42);

        // locale property populator
        $this->assertContainerBuilderHasService('foobar.populator.locale', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.locale', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.locale', '$targetProperty', 'locale');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.locale', '$sourceProperty', 'locale');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.locale', '$defaultValue', 'en');
    }
}
