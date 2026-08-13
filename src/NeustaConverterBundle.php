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
        // Order matters: both passes run in the default `beforeOptimization` stage
        // with priority 0, i.e. in registration order. DebugInfoPass has to see the
        // original populator services, not the wrappers CustomContractPopulatorPass
        // puts in their place.
        $container->addCompilerPass(new DebugInfoPass());
        $container->addCompilerPass(new CustomContractPopulatorPass());
    }
}
