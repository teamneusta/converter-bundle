<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\NeustaConverterBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @return array
     */
    public function registerBundles()
    {
        $bundles = [];

        if (\in_array($this->getEnvironment(), ['test'], true)) {
            $bundles[] = new NeustaConverterBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../config/packages/test/config_test.yaml');
        $loader->load(__DIR__ . '/../config/packages/test/services_test.yaml');
    }

    public function getCacheDir()
    {
        return \sys_get_temp_dir() . '/NeustaConverterBundle/cache';
    }

    public function getLogDir()
    {
        return \sys_get_temp_dir() . '/NeustaConverterBundle/logs';
    }
}
