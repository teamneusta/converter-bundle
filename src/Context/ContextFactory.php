<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Context;

use Neusta\ConverterBundle\Context;

/**
 * @internal
 */
final class ContextFactory
{
    public function __construct(
        /** @var iterable<ContextConfigurator> */
        private readonly iterable $configurators,
    ) {
    }

    /** @param Context|null $seed an already-resolved context configurators can check via has() to skip redundant work */
    public function create(?Context $seed = null): Context
    {
        $context = $seed ?? Context::create();

        foreach ($this->configurators as $configurator) {
            $context = $configurator->configureContext($context);
        }

        return $context;
    }
}
