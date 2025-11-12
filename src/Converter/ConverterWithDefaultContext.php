<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;

/**
 * @template TSource of object
 * @template TTarget of object
 *
 * @implements Converter<TSource, TTarget, Context>
 */
final class ConverterWithDefaultContext implements Converter
{
    /**
     * @param Converter<TSource, TTarget, Context> $inner
     */
    public function __construct(
        private readonly Converter $inner,
        private readonly Context $context,
    ) {
    }

    public function convert(object $source, ?object $ctx = null): object
    {
        $context = $this->context;

        if ($ctx) {
            // @phpstan-ignore-next-line instanceof.alwaysTrue
            if (!$ctx instanceof Context) {
                throw new \InvalidArgumentException(\sprintf('The context must be an instance of "%s".', Context::class));
            }

            $context = $context->with($ctx);
        }

        return $this->inner->convert($source, $context);
    }
}
