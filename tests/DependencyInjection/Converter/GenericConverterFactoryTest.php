<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Converter;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\FactoryRegistry;
use Neusta\ConverterBundle\DependencyInjection\Populator\ContextMappingPopulatorFactory;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetWithPropertiesFactory;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\LanguageContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\MultiValueContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\NonMappingPropertyPopulatorFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

class GenericConverterFactoryTest extends NeustaConverterExtensionTestCase
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
            new NonMappingPropertyPopulatorFactory(),
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

    public function test_with_generic_target_factory(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target' => [
                            'class' => Person::class,
                            'properties' => [
                                'mail' => 'mail@me.com',
                            ],
                        ],
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        $this->assertContainerBuilderHasService('foobar.target_factory', GenericTargetWithPropertiesFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference('foobar.target_factory'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$type', Person::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$properties', ['mail' => 'mail@me.com']);
    }

    public function test_with_generic_target_factory_as_string_shorthand(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target' => Person::class,
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar.target_factory', GenericTargetWithPropertiesFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$type', Person::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$properties', []);
    }

    public function test_with_generic_target_factory_for_unknown_type(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The target type "UnknownClass" does not exist.');

        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target' => [
                            'class' => 'UnknownClass',
                        ],
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
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
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
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
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target' => [
                            'class' => Person::class,
                        ],
                        'target_factory' => PersonFactory::class,
                        'populators' => [
                            PersonNamePopulator::class,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_without_populators_properties_and_context(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('At least one "populator", "property" or "context" must be defined.');

        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_with_unknown_converter_type(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([
            'converters' => [
                'foobar' => [
                    'unknown_type' => [],
                ],
            ],
        ]);
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

    /**
     * Populators a converter creates for its own properties/context are an implementation detail:
     * keeping them private lets the container remove or inline them, and keeps the generated ids
     * from becoming de-facto public API.
     */
    public function test_property_and_context_populators_are_private(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target' => Person::class,
                        'properties' => ['mail' => 'email'],
                        'context' => ['locale' => null],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', GenericConverter::class);
        self::assertFalse($this->container->getDefinition('foobar.populator.mail')->isPublic());
        self::assertFalse($this->container->getDefinition('foobar.populator.context.locale')->isPublic());
        self::assertFalse($this->container->getDefinition('foobar.target_factory')->isPublic());
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

    public function test_with_mapped_context_required(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'ageInYears' => [
                                'class' => AgeContext::class,
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$required', true);
    }

    public function test_with_mapped_context_and_class(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'ageInYears' => [
                                'class' => AgeContext::class,
                                'property' => 'age',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextClass', AgeContext::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextProperty', 'age');
    }

    public function test_with_mapped_context_without_context_configurators_does_not_require_class(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'ageInYears' => 'age',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextClass', null);
    }

    public function test_with_mapped_context_infers_property_from_single_property_class(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'ageInYears' => [
                                'class' => AgeContext::class,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextClass', AgeContext::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextProperty', 'age');
    }

    public function test_with_mapped_context_explicit_property_wins_over_inference(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'age' => [
                                'class' => AgeContext::class,
                                'property' => 'somethingElse',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.age', '$contextProperty', 'somethingElse');
    }

    public function test_with_mapped_context_falls_back_to_target_property_for_multi_property_class(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'someTarget' => [
                                'class' => MultiValueContext::class,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.someTarget', '$contextProperty', 'someTarget');
    }

    public function test_with_mapped_context_class_string_shortcut(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'locale' => LanguageContext::class,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.locale', '$contextClass', LanguageContext::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.locale', '$contextProperty', 'language');
    }

    public function test_with_mapped_context_non_class_string_is_still_treated_as_property(): void
    {
        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'ageInYears' => 'age',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextClass', null);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextProperty', 'age');
    }

    public function test_with_mapped_context_invalid_class(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'context' => [
                            'locale' => [
                                'class' => 'App\\Does\\Not\\Exist',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
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

    /**
     * Each type declares its own fields via its own `addConfiguration()`, called on its own node
     * (see `NonMappingPropertyPopulatorFactory`, which doesn't extend `PropertyMappingPopulatorFactory`
     * and never declares "default") - so an unsupported field is structurally absent, not merely
     * rejected after the fact.
     */
    public function test_property_with_custom_type_has_no_default_option(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "default" under "neusta_converter.converters.foobar.generic.properties.name.non_mapping"');

        $this->load([
            'converters' => [
                'foobar' => [
                    'generic' => [
                        'target_factory' => PersonFactory::class,
                        'properties' => [
                            'name' => [
                                'non_mapping' => [
                                    'default' => 'John Doe',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * `property_mapping` and `context_mapping` are mandatory built-ins (see `NeustaConverterBundle`);
     * these two tests guard that wiring by constructing an incomplete `FactoryRegistry` directly -
     * `NeustaConverterExtensionTestCase::load()` always adds both, so it can't simulate "missing".
     */
    public function test_default_populator_factory_must_be_registered(): void
    {
        $factories = new FactoryRegistry([], [new ContextMappingPopulatorFactory()]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The mandatory populator factory for the default type "property_mapping" is not registered.');

        (new GenericConverterFactory())->addConfiguration(new ArrayNodeDefinition('generic'), $factories);
    }

    public function test_context_mapping_populator_factory_must_be_registered(): void
    {
        $factories = new FactoryRegistry([], []);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The mandatory populator factory for the type "context_mapping" is not registered.');

        (new GenericConverterFactory())->addConfiguration(new ArrayNodeDefinition('generic'), $factories);
    }
}
