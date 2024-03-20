<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Support\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class ConfigureContainer
{
    /**
     * @param string $config path to a config file
     */
    public function __construct(
        private readonly string $config,
    ) {
    }

    public function configure(\TestKernel $kernel): void
    {
        $kernel->addTestConfig($this->config);
    }
}
