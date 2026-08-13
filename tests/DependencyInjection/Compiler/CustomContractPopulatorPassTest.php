<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\DependencyInjection\Compiler\CustomContractPopulatorPass;
use Neusta\ConverterBundle\Populator\CustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\CustomContractPersonNamePopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\NonNullableContextCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\ReorderedCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class CustomContractPopulatorPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    public function testWrapsACustomContractPopulator(): void
    {
        $this->registerPopulator('app.populator', CustomContractPersonNamePopulator::class);
        $this->registerConverter('app.converter', ['app.populator']);

        $this->process();

        self::assertEquals(
            [new Reference('app.populator.populator')],
            $this->container->getDefinition('app.converter')->getArgument('$populators'),
        );

        $wrapper = $this->container->getDefinition('app.populator.populator');
        self::assertSame(CustomContractPopulator::class, $wrapper->getClass());
        self::assertSame(['source', 'target', 'context'], $wrapper->getArgument('$parameterOrder')->getArgument(0));

        $closure = $wrapper->getArgument('$populator');
        self::assertEquals([new Reference('app.populator'), 'populateName'], $closure->getArgument(0));
    }

    public function testKeepsTheParameterOrderOfTheContract(): void
    {
        $this->registerPopulator('app.populator', ReorderedCustomContractPopulator::class);
        $this->registerConverter('app.converter', ['app.populator']);

        $this->process();

        self::assertSame(
            ['target', 'context', 'source'],
            $this->container->getDefinition('app.populator.populator')->getArgument('$parameterOrder')->getArgument(0),
        );
    }

    public function testLeavesRegularPopulatorsUntouched(): void
    {
        $this->registerPopulator('app.populator', PersonNamePopulator::class);
        $this->registerConverter('app.converter', ['app.populator']);

        $this->process();

        self::assertEquals(
            [new Reference('app.populator')],
            $this->container->getDefinition('app.converter')->getArgument('$populators'),
        );
        self::assertFalse($this->container->hasDefinition('app.populator.populator'));
    }

    public function testRegistersTheWrapperOnlyOnceForMultipleConverters(): void
    {
        $this->registerPopulator('app.populator', CustomContractPersonNamePopulator::class);
        $this->registerConverter('app.converter_a', ['app.populator']);
        $this->registerConverter('app.converter_b', ['app.populator']);

        $this->process();

        foreach (['app.converter_a', 'app.converter_b'] as $converterId) {
            self::assertEquals(
                [new Reference('app.populator.populator')],
                $this->container->getDefinition($converterId)->getArgument('$populators'),
            );
        }
    }

    /**
     * The populator's class is only known through its parent definition.
     */
    public function testResolvesTheClassOfAChildDefinition(): void
    {
        $this->container->register('app.abstract_populator', CustomContractPersonNamePopulator::class)->setAbstract(true);
        $this->container->setDefinition('app.populator', new ChildDefinition('app.abstract_populator'));
        $this->registerConverter('app.converter', ['app.populator']);

        $this->process();

        self::assertTrue($this->container->hasDefinition('app.populator.populator'));
    }

    public function testFailsWhenTheConverterHasNoPopulatorsArgument(): void
    {
        $this->container->register('app.converter', GenericConverter::class)
            ->addTag('neusta_converter.converter');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "app.converter" is tagged as "neusta_converter.converter" but has no "$populators" argument.');

        $this->process();
    }

    public function testFailsForAContractWithANonNullableContext(): void
    {
        $this->registerPopulator('app.populator', NonNullableContextCustomContractPopulator::class);
        $this->registerConverter('app.converter', ['app.populator']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('annotated with #[Context] must be nullable');

        $this->process();
    }

    public function testFailsWhenThePopulatorClassCannotBeFound(): void
    {
        $this->container->register('app.populator', 'App\\Does\\Not\\Exist');
        $this->registerConverter('app.converter', ['app.populator']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "App\\Does\\Not\\Exist" used for service "app.populator" cannot be found.');

        $this->process();
    }

    private function process(): void
    {
        (new CustomContractPopulatorPass())->process($this->container);
    }

    /**
     * @param class-string $class
     */
    private function registerPopulator(string $id, string $class): void
    {
        $this->container->register($id, $class);
    }

    /**
     * @param list<string> $populatorIds
     */
    private function registerConverter(string $id, array $populatorIds): void
    {
        $this->container->register($id, GenericConverter::class)
            ->addTag('neusta_converter.converter')
            ->setArguments([
                '$factory' => new Definition(),
                '$populators' => array_map(static fn (string $populatorId) => new Reference($populatorId), $populatorIds),
            ]);
    }
}
