<?php declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Model;

class BundleInfo
{
    public function __construct(
        public readonly string $bundleName,
        public readonly string $bundleClass,
        public readonly string $namespace,
        public int $serviceCount = 0,
    ) {
    }
}
