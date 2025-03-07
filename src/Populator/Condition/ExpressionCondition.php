<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
final class ExpressionCondition
{
    public function __construct(
        private ExpressionLanguage $expressionLanguage,
        private string $expression,
    ) {
    }

    /**
     * @param TTarget  $target
     * @param TSource  $source
     * @param TContext $ctx
     */
    public function __invoke(object $target, object $source, ?object $ctx = null): bool
    {
        return $this->expressionLanguage->evaluate($this->expression, [
            'source' => $source,
            'target' => $target,
            'context' => $ctx,
        ]);
    }
}
