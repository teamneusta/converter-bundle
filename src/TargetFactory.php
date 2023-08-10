<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\Converter\Context\ContextInterface;

/**
 * @template TTarget of object
 * @template TContext of ContextInterface|null
 */
interface TargetFactory
{
    /**
     * @param TContext $ctx
     *
     * @return TTarget
     */
    public function create(?ContextInterface $ctx = null): object;
}
