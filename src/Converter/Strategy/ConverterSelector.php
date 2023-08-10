<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Strategy;

use Neusta\ConverterBundle\Converter\Context\ContextInterface;

/**
 * @template TSource of object
 * @template TContext of ContextInterface|null
 */
interface ConverterSelector
{
    /**
     * @param TSource $source
     * @param TContext $ctx
     */
    public function selectConverter(object $source, ?ContextInterface $ctx = null): string;
}
