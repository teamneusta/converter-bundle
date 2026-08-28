<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;

/**
 * @internal wired up automatically by the bundle configuration when "context_configurators" are used
 *
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
        private readonly ContextFactory $contextFactory,
    ) {
    }

    public function convert(object $source, ?object $ctx = null): object
    {
        $seed = null;

        if ($ctx) {
            // @phpstan-ignore instanceof.alwaysTrue
            if (!$ctx instanceof Context) {
                throw new \InvalidArgumentException(\sprintf('The context must be an instance of "%s".', Context::class));
            }

            $seed = $ctx;
        }

        // Seeded so configurators can skip redundant work via $ctx->has()
        $context = $this->contextFactory->create($seed);

        if ($seed) {
            $context = $context->with($seed);
        }

        return $this->inner->convert($source, $context);
    }
}
