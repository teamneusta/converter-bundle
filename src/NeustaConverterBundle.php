<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\DependencyInjection\Compiler\ServiceInspectorPass;
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
        $container->addCompilerPass(new ServiceInspectorPass());
    }
}
