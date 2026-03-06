<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder;

/**
 * @internal
 *
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 *
 * @implements Populator<TSource, TTarget, TContext>
 */
final class CustomContractPopulator implements Populator
{
    /**
     * The closure is invoked with source, target, and optional context in the
     * order defined by $parameterOrder. Because context may appear in any
     * position, all argument positions are typed as object|null.
     *
     * @param \Closure(object|null, object|null, object|null):void $populator
     */
    public function __construct(
        private readonly \Closure $populator,
        private readonly ParameterOrder $parameterOrder,
    ) {
    }

    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        ($this->populator)(...$this->parameterOrder->resolveArgs($source, $target, $ctx));
    }
}
