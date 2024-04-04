<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle;

use Neusta\ConverterBundle\DependencyInjection\Converter\GenericConverterFactory;
use Neusta\ConverterBundle\DependencyInjection\NeustaConverterExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NeustaConverterBundle extends Bundle
{
    public const ALIAS = 'neusta_converter';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $extension = $container->getExtension(self::ALIAS);
        \assert($extension instanceof NeustaConverterExtension);

        $extension->addConverterFactory(new GenericConverterFactory());
    }
}
