<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\Mapper;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Exception\ConverterException;

/**
 * @template TSource of object
 * @template TTarget of object
 * @template TContext of object|null
 */
final class ConverterMapper
{
    /**
     * @param Converter<TSource, TTarget, TContext> $converter
     */
    public function __construct(
        private readonly Converter $converter,
    ) {
    }

    /**
     * @param TSource  $source
     * @param TContext $ctx
     *
     * @return TTarget
     *
     * @throws ConverterException
     */
    public function __invoke(object $source, ?object $ctx = null): object
    {
        return $this->converter->convert($source, $ctx);
    }
}
