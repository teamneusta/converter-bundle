<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Support\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class RegisterBundle
{
    /**
     * @param class-string $bundle
     */
    public function __construct(
        private readonly string $bundle,
    ) {
    }

    public function configure(\TestKernel $kernel): void
    {
        $kernel->addTestBundle($this->bundle);
    }
}
