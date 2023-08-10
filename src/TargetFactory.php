<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\Converter\Context\GenericContext;

/**
 * @template TTarget of object
 * @template TContext of object|null
 */
interface TargetFactory
{
    /**
     * @param TContext $ctx
     *
     * @return TTarget
     */
    public function create(?object $ctx = null): object;
}
