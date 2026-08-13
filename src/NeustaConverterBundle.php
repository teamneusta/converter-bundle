<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\DependencyInjection\Compiler\CustomContractPopulatorPass;
use Neusta\ConverterBundle\DependencyInjection\Compiler\DebugInfoPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeustaConverterBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        // Order matters: DebugInfoPass has to see the original populator services,
        // not the wrappers CustomContractPopulatorPass puts in their place. Both
        // run in the `beforeOptimization` stage, so DebugInfoPass is given a
        // higher priority to make it run first, instead of relying on registration order.
        $container->addCompilerPass(new DebugInfoPass(), priority: 10);
        $container->addCompilerPass(new CustomContractPopulatorPass());
    }
}
