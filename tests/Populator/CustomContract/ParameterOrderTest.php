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
     * @dataProvider validMethods
     */
    public function testFromReflectionResolvesRoles(string $method, ParameterOrder $expected): void
    {
        self::assertSame($expected, self::orderOf($method));
    }

    /**
     * @return iterable<string, array{string, ParameterOrder}>
     */
    public static function validMethods(): iterable
    {
        yield 'natural order' => ['sourceTargetContext', ParameterOrder::SourceTargetContext];
        yield 'context in the middle' => ['targetContextSource', ParameterOrder::TargetContextSource];
        yield 'without context' => ['sourceTarget', ParameterOrder::SourceTarget];
        yield 'target before source' => ['targetSource', ParameterOrder::TargetSource];
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
            'Method "' . ParameterOrderMethods::class . '::twoSources" must contain exactly one "source" role and exactly one "target" role.',
        ];
        yield 'missing target' => [
            'missingTarget',
            'Method "' . ParameterOrderMethods::class . '::missingTarget" must contain exactly one "source" role and exactly one "target" role.',
        ];
    }

    public function testResolveArgsFollowsTheDeclaredOrder(): void
    {
        $source = new User();
        $target = new Person();
        $context = new GenericContext();

        self::assertSame(
            [$target, $context, $source],
            ParameterOrder::TargetContextSource->resolveArgs($source, $target, $context),
        );
    }

    public function testResolveArgsOmitsAnUndeclaredContext(): void
    {
        $source = new User();
        $target = new Person();

        self::assertSame(
            [$source, $target],
            ParameterOrder::SourceTarget->resolveArgs($source, $target, new GenericContext()),
        );
    }

    public function testResolveArgsPassesNullForAMissingContext(): void
    {
        $source = new User();
        $target = new Person();

        self::assertSame(
            [$source, $target, null],
            ParameterOrder::SourceTargetContext->resolveArgs($source, $target, null),
        );
    }

    /**
     * The enum is only able to make invalid orders unrepresentable as long as it
     * covers every valid one - and nothing else.
     */
    public function testEveryValidRoleCombinationHasExactlyOneCase(): void
    {
        $combinations = [
            ...self::permutationsOf(['source', 'target']),
            ...self::permutationsOf(['source', 'target', 'context']),
        ];

        foreach ($combinations as $roles) {
            self::assertNotNull(
                ParameterOrder::tryFrom($value = implode('|', $roles)),
                \sprintf('No enum case for the valid parameter order "%s".', $value),
            );
        }

        self::assertCount(
            \count($combinations),
            ParameterOrder::cases(),
            'The enum declares cases beyond the valid parameter orders.',
        );
    }

    /**
     * @param list<string> $items
     *
     * @return list<list<string>>
     */
    private static function permutationsOf(array $items): array
    {
        if (1 >= \count($items)) {
            return [$items];
        }

        $permutations = [];
        foreach ($items as $index => $item) {
            $rest = $items;
            unset($rest[$index]);

            foreach (self::permutationsOf(array_values($rest)) as $permutation) {
                $permutations[] = [$item, ...$permutation];
            }
        }

        return $permutations;
    }

    private static function orderOf(string $method): ParameterOrder
    {
        return ParameterOrder::fromReflection(new \ReflectionMethod(ParameterOrderMethods::class, $method));
    }
}
