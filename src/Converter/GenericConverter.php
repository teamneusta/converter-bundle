<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator;
use Neusta\ConverterBundle\TargetFactory;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null = null
 *
 * @implements Converter<TSource, TTarget, TContext>
 */
final class GenericConverter implements Converter
{
    /** @var array<class-string, true> classes already warned about, so repeated calls with the same offending type don't re-pay for it */
    private array $deprecatedCtxClassesWarned = [];

    /**
     * @param TargetFactory<TTarget, TContext>             $factory
     * @param array<Populator<TSource, TTarget, TContext>> $populators
     */
    public function __construct(
        private TargetFactory $factory,
        private array $populators,
    ) {
    }

    public function convert(object $source, ?object $ctx = null): object
    {
        if (null !== $ctx && !$ctx instanceof Context && !$ctx instanceof GenericContext
            && !isset($this->deprecatedCtxClassesWarned[$ctx::class])
        ) {
            $this->deprecatedCtxClassesWarned[$ctx::class] = true;

            trigger_deprecation(
                'teamneusta/converter-bundle',
                '1.11',
                'Passing a "%s" instance as $ctx that is not an instance of "%s" is deprecated and will not be supported anymore in 2.0. If this converter maps any "context:" properties, it already throws an InvalidArgumentException today - pass a (deprecated) "%s" instance instead to keep it working.',
                $ctx::class,
                Context::class,
                GenericContext::class,
            );
        }

        $target = $this->factory->create($ctx);

        foreach ($this->populators as $populator) {
            $populator->populate($target, $source, $ctx);
        }

        return $target;
    }
}
