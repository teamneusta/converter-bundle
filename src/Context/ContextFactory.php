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
    ) {}

    public function create(): Context
    {
        $context = Context::create();

        foreach ($this->configurators as $configurator) {
            $context = $configurator->configureContext($context);
        }

        return $context;
    }
}
