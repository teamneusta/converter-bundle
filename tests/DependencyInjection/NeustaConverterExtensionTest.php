<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\Populator\ArrayConvertingPopulator;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\ConvertingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetWithPropertiesFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;

class NeustaConverterExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new NeustaConverterExtension(),
        ];
    }

    public function test_with_generic_converter(): void
    {
        $this->load([
            'converterSuffix' => 'Converter',
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasAlias(Converter::class . ' $foobarConverter', 'foobar');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference(PersonFactory::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [new Reference(PersonNamePopulator::class)]);
    }

    public function test_with_generic_target_factory(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
                    'target' => [
                        'class' => Person::class,
                    ],
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);

        // converter
        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasService('foobar.target_factory', GenericTargetWithPropertiesFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference('foobar.target_factory'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$type', Person::class);
    }

    public function test_with_generic_target_factory_for_unknown_type(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The target type "UnknownClass" does not exist.');

        $this->load([
            'converter' => [
                'foobar' => [
                    'target' => [
                        'class' => 'UnknownClass',
                    ],
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_without_target_and_target_factory(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Either "target" or "target_factory" must be defined.');

        $this->load([
            'converter' => [
                'foobar' => [
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_with_target_and_target_factory(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Either "target" or "target_factory" must be defined, but not both.');

        $this->load([
            'converter' => [
                'foobar' => [
                    'target' => [
                        'class' => Person::class,
                    ],
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_with_mapped_properties(): void
    {
        $this->load([
            'converterSuffix' => 'Converter',
            'converter' => [
                'foobar' => [
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
            'converterSuffix' => 'Converter',
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'context' => [
                        'name' => null,
                        'ageInYears' => 'age',
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

    public function test_with_converting_populator(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
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
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => null,
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

    public function test_with_converting_populator_with_array_converting_populator_config(): void
    {
        $this->expectExceptionMessage('The "property.<target>.source_array_item" option is only supported for array converting populators.');

        $this->load([
            'populator' => [
                'foobar' => [
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => [
                            'source_array_item' => 'value',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_with_array_converting_populator(): void
    {
        $this->load([
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => 'sourceTest',
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
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => null,
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
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'targetTest' => [
                            'source' => 'sourceTest',
                            'source_array_item' => 'value',
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
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => [
                            'source' => null,
                            'source_array_item' => 'value',
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
            'populator' => [
                'foobar' => [
                    'populator' => ArrayConvertingPopulator::class,
                    'converter' => GenericConverter::class,
                    'property' => [
                        'test' => [
                            'source_array_item' => 'value',
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

    public function test_with_array_converting_populator_with_default_value(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
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

    /**
     * Assert that the ContainerBuilder for this test has a public service definition with the given id and class.
     */
    protected function assertContainerBuilderHasPublicService(string $serviceId, ?string $expectedClass = null): void
    {
        $this->assertContainerBuilderHasService($serviceId, $expectedClass);
        $this->assertTrue(
            $this->container->getDefinition('foobar')->isPublic(),
            \sprintf('service definition "%s" is "public"', $serviceId),
        );
    }
}
