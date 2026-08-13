<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\PopulatorContract;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\AmbiguousCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\CustomContractPersonNamePopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\DoubleAnnotatedCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\ExtendedMethodCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\InheritingCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\InvalidCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\MultipleMethodsCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\ReorderedCustomContractPopulator;
use PHPUnit\Framework\TestCase;

final class PopulatorContractTest extends TestCase
{
    public function testResolvesMethodAndParameterOrder(): void
    {
        $contract = PopulatorContract::fromReflection(new \ReflectionClass(CustomContractPersonNamePopulator::class));

        self::assertSame('populateName', $contract->methodName);
        self::assertSame(['source', 'target', 'context'], $contract->parameterOrder->toArray());
    }

    public function testResolvesAReorderedContract(): void
    {
        $contract = PopulatorContract::fromReflection(new \ReflectionClass(ReorderedCustomContractPopulator::class));

        self::assertSame('populateName', $contract->methodName);
        self::assertSame(['target', 'context', 'source'], $contract->parameterOrder->toArray());
    }

    /**
     * An interface extending a contract must not count as a second contract.
     */
    public function testResolvesAnInheritedContract(): void
    {
        $contract = PopulatorContract::fromReflection(new \ReflectionClass(InheritingCustomContractPopulator::class));

        self::assertSame('populateName', $contract->methodName);
        self::assertSame(['source', 'target', 'context'], $contract->parameterOrder->toArray());
    }

    /**
     * Both interfaces of the hierarchy are annotated - the most specific one wins.
     */
    public function testResolvesTheMostDerivedOfNestedContracts(): void
    {
        $contract = PopulatorContract::fromReflection(new \ReflectionClass(DoubleAnnotatedCustomContractPopulator::class));

        self::assertSame('populateName', $contract->methodName);
        self::assertSame(['source', 'target', 'context'], $contract->parameterOrder->toArray());
    }

    public function testFailsWithoutAContract(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('does not implement a custom populator contract interface');

        PopulatorContract::fromReflection(new \ReflectionClass(InvalidCustomContractPopulator::class));
    }

    public function testFailsWithMultipleContracts(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('implements multiple custom populator contract interfaces');

        PopulatorContract::fromReflection(new \ReflectionClass(AmbiguousCustomContractPopulator::class));
    }

    public function testFailsWhenTheContractDeclaresMultipleMethods(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must declare exactly one method');

        PopulatorContract::fromReflection(new \ReflectionClass(MultipleMethodsCustomContractPopulator::class));
    }

    /**
     * Inherited methods count too, so extending a contract with an additional
     * method makes it ambiguous.
     */
    public function testFailsWhenTheContractInheritsAnAdditionalMethod(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must declare exactly one method, got 2: populateAge, populateName');

        PopulatorContract::fromReflection(new \ReflectionClass(ExtendedMethodCustomContractPopulator::class));
    }

    /**
     * Resolution is memoized per contract interface.
     */
    public function testResolvesTheSameContractOnlyOnce(): void
    {
        self::assertSame(
            PopulatorContract::fromReflection(new \ReflectionClass(CustomContractPersonNamePopulator::class)),
            PopulatorContract::fromReflection(new \ReflectionClass(InheritingCustomContractPopulator::class)),
        );
    }

    /**
     * @dataProvider classes
     */
    public function testIsImplementedBy(string $class, bool $expected): void
    {
        self::assertSame($expected, PopulatorContract::isImplementedBy(new \ReflectionClass($class)));
    }

    /**
     * @return iterable<string, array{class-string, bool}>
     */
    public static function classes(): iterable
    {
        yield 'contract populator' => [CustomContractPersonNamePopulator::class, true];
        yield 'inherited contract' => [InheritingCustomContractPopulator::class, true];
        yield 'no contract' => [InvalidCustomContractPopulator::class, false];
    }

    /**
     * Detection must stay cheap and side-effect free even for a class whose
     * contract would not survive validation.
     */
    public function testIsImplementedByDoesNotValidateTheContract(): void
    {
        self::assertTrue(PopulatorContract::isImplementedBy(new \ReflectionClass(MultipleMethodsCustomContractPopulator::class)));
    }
}
