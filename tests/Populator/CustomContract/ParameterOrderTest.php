<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator\CustomContract;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\ParameterOrderMethods;
use PHPUnit\Framework\TestCase;

final class ParameterOrderTest extends TestCase
{
    /**
     * @param list<'source'|'target'|'context'> $expected
     *
     * @dataProvider validMethods
     */
    public function testFromReflectionResolvesRoles(string $method, array $expected): void
    {
        self::assertSame($expected, self::orderOf($method)->toArray());
    }

    /**
     * @return iterable<string, array{string, list<'source'|'target'|'context'>}>
     */
    public static function validMethods(): iterable
    {
        yield 'natural order' => ['sourceTargetContext', ['source', 'target', 'context']];
        yield 'context in the middle' => ['targetContextSource', ['target', 'context', 'source']];
        yield 'without context' => ['sourceTarget', ['source', 'target']];
        yield 'target before source' => ['targetSource', ['target', 'source']];
    }

    /**
     * @dataProvider invalidMethods
     */
    public function testFromReflectionRejectsInvalidSignatures(string $method, string $expectedMessage): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        self::orderOf($method);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidMethods(): iterable
    {
        yield 'non-nullable context' => [
            'nonNullableContext',
            'Parameter "$context" of method "' . ParameterOrderMethods::class . '::nonNullableContext" annotated with #[Context] must be nullable.',
        ];
        yield 'multiple roles on one parameter' => [
            'multipleRoles',
            'must be annotated with exactly one of #[Source], #[Target] or #[Context], got: source, target',
        ];
        yield 'unannotated parameter' => [
            'unannotated',
            'Parameter "$user" of method "' . ParameterOrderMethods::class . '::unannotated" must be annotated with #[Source], #[Target] or #[Context].',
        ];
        yield 'two source parameters' => [
            'twoSources',
            'must contain exactly one "source" role and exactly one "target" role',
        ];
        yield 'missing target' => [
            'missingTarget',
            'must contain exactly one "source" role and exactly one "target" role',
        ];
    }

    public function testResolveArgsFollowsTheDeclaredOrder(): void
    {
        $source = new User();
        $target = new Person();
        $context = new GenericContext();

        self::assertSame(
            [$target, $context, $source],
            self::orderOf('targetContextSource')->resolveArgs($source, $target, $context),
        );
    }

    public function testResolveArgsOmitsAnUndeclaredContext(): void
    {
        $source = new User();
        $target = new Person();

        self::assertSame(
            [$source, $target],
            self::orderOf('sourceTarget')->resolveArgs($source, $target, new GenericContext()),
        );
    }

    public function testResolveArgsPassesNullForAMissingContext(): void
    {
        $source = new User();
        $target = new Person();

        self::assertSame(
            [$source, $target, null],
            self::orderOf('sourceTargetContext')->resolveArgs($source, $target, null),
        );
    }

    public function testFromArrayAcceptsValidOrders(): void
    {
        self::assertSame(['context', 'target', 'source'], ParameterOrder::fromArray(['context', 'target', 'source'])->toArray());
    }

    /**
     * @param list<mixed> $order
     *
     * @dataProvider invalidArrays
     */
    public function testFromArrayRejectsInvalidOrders(array $order, string $expectedMessage): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        ParameterOrder::fromArray($order);
    }

    /**
     * @return iterable<string, array{list<mixed>, string}>
     */
    public static function invalidArrays(): iterable
    {
        yield 'unknown role' => [['source', 'target', 'ctx'], 'Parameter order array contains invalid role "ctx" at index 2.'];
        yield 'non-string role' => [['source', 'target', 42], 'Parameter order array contains invalid role "42" at index 2.'];
        yield 'missing target' => [['source'], 'must contain exactly one "source" role and exactly one "target" role'];
        yield 'duplicate target' => [['source', 'target', 'target'], 'must contain exactly one "source" role and exactly one "target" role'];
        yield 'two contexts' => [['source', 'target', 'context', 'context'], 'must not contain more than one "context" role'];
    }

    private static function orderOf(string $method): ParameterOrder
    {
        return ParameterOrder::fromReflection(new \ReflectionMethod(ParameterOrderMethods::class, $method));
    }
}
