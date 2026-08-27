<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Populator;

use Neusta\ConverterBundle\DependencyInjection\Populator\ContextMappingPopulatorFactory;
use Neusta\ConverterBundle\Populator\ContextMappingPopulator;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\LanguageContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\MultiValueContext;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Reference;

class ContextMappingPopulatorFactoryTest extends NeustaConverterExtensionTestCase
{
    protected function getPopulatorFactories(): array
    {
        return [
            new ContextMappingPopulatorFactory(),
        ];
    }

    public function test_with_context_mapping_populator(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'context_mapping' => [
                        'target' => 'ageInYears',
                        'class' => AgeContext::class,
                        'property' => 'age',
                        'required' => true,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('foobar', ContextMappingPopulator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$accessor', new Reference('property_accessor'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$targetProperty', 'ageInYears');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$contextClass', AgeContext::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$contextProperty', 'age');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$required', true);
    }

    /**
     * A populator declared on its own is meant to be fetched and referenced, unlike the ones a
     * converter creates for its own `context`.
     */
    public function test_standalone_populator_is_public(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'context_mapping' => [
                        'target' => 'locale',
                        'class' => LanguageContext::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasPublicService('foobar', ContextMappingPopulator::class);
    }

    public function test_infers_property_from_single_property_class(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'context_mapping' => [
                        'target' => 'ageInYears',
                        'class' => AgeContext::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$contextProperty', 'age');
    }

    public function test_falls_back_to_target_property_for_multi_property_class(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'context_mapping' => [
                        'target' => 'someTarget',
                        'class' => MultiValueContext::class,
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('foobar', '$contextProperty', 'someTarget');
    }

    /**
     * Under `populators`, `target` sits as a required sibling on the very same node as the
     * `beforeNormalization()` shortcuts below, so a bare scalar/null value there can never carry it -
     * the shortcuts only ever fire through `GenericConverterFactory`'s `context` key, where the
     * target property is the array key instead of a sibling field (covered by
     * `GenericConverterFactoryTest::test_with_mapped_context_class_string_shortcut()` and friends).
     * `class`/`property`/`required` are still reachable here in their explicit, long form.
     */
    public function test_invalid_class(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([
            'populators' => [
                'foobar' => [
                    'context_mapping' => [
                        'target' => 'locale',
                        'class' => 'App\\Does\\Not\\Exist',
                    ],
                ],
            ],
        ]);
    }
}
