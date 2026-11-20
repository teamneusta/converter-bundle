<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle;

use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Neusta\ConverterBundle\NeustaConverterBundle;
use Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\DependencyInjection\ReversingConverterFactory;
use Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\DependencyInjection\UppercasePopulatorFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Stands in for a third-party bundle (or the app itself) that adds its own converter and populator
 * types to `neusta_converter`.
 */
final class ExtendingBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $extension = $container->getExtension(NeustaConverterBundle::ALIAS);
        \assert($extension instanceof NeustaConverterExtension);

        $extension->addConverterFactory(new ReversingConverterFactory());
        $extension->addPopulatorFactory(new UppercasePopulatorFactory());
    }
}
