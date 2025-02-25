<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Populator;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
class ConditionalPopulator implements Populator
{
    /**
     * @param Populator<TSource, TTarget, TContext> $populator
     */
    public function __construct(
        private Populator $populator,
        private \Closure $condition,
    ) {
    }

    /**
     * @param TTarget  $target
     * @param TSource  $source
     * @param TContext $ctx
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        if (($this->condition)($target, $source, $ctx)) {
            $this->populator->populate($target, $source, $ctx);
        }
    }
}
