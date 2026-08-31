<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection;

use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Populator\PropertyMappingPopulator;
use Neusta\ConverterBundle\Target\GenericTargetWithPropertiesFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContextConfigurator;
use Neusta\ConverterBundle\Tests\Fixtures\Converter\CustomConverter;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Factory\PersonFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The deprecated `neusta_converter.converter` section delegates to the `GenericConverterFactory`,
 * so it has to stay in lockstep with `neusta_converter.converters.<id>.generic`.
 *
 * @todo remove together with the deprecated config in 2.0
 */
class DeprecatedConverterSectionTest extends NeustaConverterExtensionTestCase
{
    protected function getConverterFactories(): array
    {
        return [
            new GenericConverterFactory(),
        ];
    }

    public function test_with_target_factory(): void
    {
        $this->load([
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

    /**
     * Proves the delegation: features added to the "generic" converter type are inherited by the
     * deprecated section without duplicating the config tree.
     */
    public function test_with_generic_target_factory(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
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
        ]);

        $this->assertContainerBuilderHasService('foobar.target_factory', GenericTargetWithPropertiesFactory::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference('foobar.target_factory'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$type', Person::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.target_factory', '$properties', ['mail' => 'mail@me.com']);
    }

    public function test_with_custom_converter_class(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
                    'converter' => CustomConverter::class,
                    'target_factory' => PersonFactory::class,
                    'populators' => [
                        PersonNamePopulator::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', CustomConverter::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$factory', new Reference(PersonFactory::class));
    }

    public function test_with_mapped_properties(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'properties' => [
                        'name' => null,
                        'ageInYears' => 'age',
                        'fullName?' => null,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$populators', [
            new Reference('foobar.populator.name'),
            new Reference('foobar.populator.ageInYears'),
            new Reference('foobar.populator.fullName'),
        ]);

        $this->assertContainerBuilderHasService('foobar.populator.ageInYears', PropertyMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.ageInYears', '$sourceProperty', 'age');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.fullName', '$skipNull', true);
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

    /**
     * Proves the delegation for the new "context" shape (class/property/required) as well.
     */
    public function test_with_mapped_context_and_class(): void
    {
        $this->load([
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'context' => [
                        'ageInYears' => [
                            'class' => AgeContext::class,
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar.populator.context.ageInYears', ContextMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextClass', AgeContext::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$contextProperty', 'age');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar.populator.context.ageInYears', '$required', true);
    }

    /**
     * "context_configurators" also works flattened on the deprecated section - global and local -
     * and decorates the converter with `ConverterWithDefaultContext` just like the new section does.
     */
    public function test_with_context_configurators(): void
    {
        $this->load([
            'context_configurators' => [AgeContextConfigurator::class],
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'context' => [
                        'ageInYears' => ['class' => AgeContext::class],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar.context.factory', ContextFactory::class);
        $this->assertContainerBuilderHasService('foobar.decorator.context', ConverterWithDefaultContext::class);
    }

    public function test_with_mapped_context_missing_class_and_context_configurators(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "context.ageInYears.class" option is required for converter "foobar" because "context_configurators" are configured for it.');

        $this->load([
            'context_configurators' => [AgeContextConfigurator::class],
            'converter' => [
                'foobar' => [
                    'target_factory' => PersonFactory::class,
                    'context' => [
                        'ageInYears' => 'age',
                    ],
                ],
            ],
        ]);
    }
}
