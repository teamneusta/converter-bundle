<?php declare(strict_types=1);

namespace Neusta\ConverterBundle\Debug\Builder;

use Neusta\ConverterBundle\Debug\Model\BundleInfo;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleInfoBuilder
{
    /** @var array<BundleInfo> */
    private array $bundles = [];

    public function __construct(KernelInterface $kernel)
    {
        foreach ($kernel->getBundles() as $bundleName => $bundle) {
            $class = $bundle::class;
            $this->bundles[$bundleName] = new BundleInfo(
                $bundleName,
                $class,
                (new \ReflectionClass($class))->getNamespaceName(),
                0, // initialize with 0, will be counted later
            );
        }
    }

    public function buildFromDebugInfo(DebugInfo $debugInfo): void
    {
        foreach ($debugInfo->services() as $id => $serviceInfo) {
            $serviceInfo->setBundleName($this->getBundleNameForClass($serviceInfo->class));
        }
    }

    /**
     * @return BundleInfo[]
     */
    public function getBundles(): array
    {
        return $this->bundles;
    }

    public function countService(string $class): void
    {
        foreach ($this->bundles as $bundleName => $bundle) {
            if (str_starts_with($class, $bundle->namespace)) {
                ++$this->bundles[$bundleName]->serviceCount;

                return;
            }
        }
    }

    public function getBundleNameForClass(string $class): string
    {
        foreach ($this->bundles as $bundleName => $bundle) {
            if (str_starts_with($class, $bundle->namespace)) {
                return $bundleName;
            }
        }

        return 'no-bundle';
    }
}
