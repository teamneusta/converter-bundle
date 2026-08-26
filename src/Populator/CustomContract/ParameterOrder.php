<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;

/**
 * The order in which a contract method declares its source, target, and context parameters.
 *
 * @internal
 */
enum ParameterOrder: string
{
    case SourceTarget = 'source|target';
    case TargetSource = 'target|source';
    case SourceTargetContext = 'source|target|context';
    case SourceContextTarget = 'source|context|target';
    case TargetSourceContext = 'target|source|context';
    case TargetContextSource = 'target|context|source';
    case ContextSourceTarget = 'context|source|target';
    case ContextTargetSource = 'context|target|source';

    /** @var array<'source'|'target'|'context', class-string> */
    private const ROLE_ATTRIBUTES = [
        'source' => Source::class,
        'target' => Target::class,
        'context' => Context::class,
    ];

    public static function fromReflection(\ReflectionMethod $method): self
    {
        $roles = array_map(self::resolveRole(...), $method->getParameters());
        $roleCounts = array_count_values($roles);

        // Checked explicitly rather than relying on self::from() below, whose
        // ValueError would not tell the developer which method is at fault.
        if (1 !== ($roleCounts['source'] ?? 0) || 1 !== ($roleCounts['target'] ?? 0)) {
            throw new \LogicException(\sprintf(
                'Method "%s::%s" must contain exactly one "source" role and exactly one "target" role.',
                $method->class,
                $method->name,
            ));
        }

        if (($roleCounts['context'] ?? 0) > 1) {
            throw new \LogicException(\sprintf(
                'Method "%s::%s" must not contain more than one "context" role.',
                $method->class,
                $method->name,
            ));
        }

        return self::from(implode('|', $roles));
    }

    /** @return list<object|null> */
    public function resolveArgs(object $source, object $target, ?object $context): array
    {
        return match ($this) {
            self::SourceTarget => [$source, $target],
            self::TargetSource => [$target, $source],
            self::SourceTargetContext => [$source, $target, $context],
            self::SourceContextTarget => [$source, $context, $target],
            self::TargetSourceContext => [$target, $source, $context],
            self::TargetContextSource => [$target, $context, $source],
            self::ContextSourceTarget => [$context, $source, $target],
            self::ContextTargetSource => [$context, $target, $source],
        };
    }

    /**
     * @return 'source'|'target'|'context'
     */
    private static function resolveRole(\ReflectionParameter $parameter): string
    {
        $roles = [];
        foreach (self::ROLE_ATTRIBUTES as $role => $attribute) {
            if ($count = \count($parameter->getAttributes($attribute))) {
                array_push($roles, ...array_fill(0, $count, $role));
            }
        }

        if ([] === $roles) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" must be annotated with #[Source], #[Target] or #[Context].',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
            ));
        }

        if (1 < \count($roles)) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" must be annotated with exactly one of #[Source], #[Target] or #[Context], got: %s.',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
                implode(', ', $roles),
            ));
        }

        if ('context' === $roles[0] && !$parameter->allowsNull()) {
            throw new \LogicException(\sprintf(
                'Parameter "$%s" of method "%s" annotated with #[Context] must be nullable.',
                $parameter->name,
                self::describeDeclaringMethod($parameter),
            ));
        }

        return $roles[0];
    }

    private static function describeDeclaringMethod(\ReflectionParameter $parameter): string
    {
        return \sprintf(
            '%s::%s',
            $parameter->getDeclaringClass()?->name,
            $parameter->getDeclaringFunction()->name,
        );
    }
}
