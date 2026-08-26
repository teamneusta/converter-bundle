<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\DependencyInjection\Compiler\DebugInfoPass;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\CustomContractPersonNamePopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract\InvalidCustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Populator\PersonNamePopulator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class DebugInfoPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->register(DebugInfo::class, DebugInfo::class);
    }

    public function testCollectsRegularPopulators(): void
    {
        $this->container->register('app.populator', PersonNamePopulator::class);

        $this->process();

        self::assertSame(['populator', PersonNamePopulator::class], $this->collectedInfoFor('app.populator'));
    }

    /**
     * Populators implementing a custom contract do not implement the generic
     * Populator interface, but must still show up in the debug output.
     */
    public function testCollectsCustomContractPopulators(): void
    {
        $this->container->register('app.populator', CustomContractPersonNamePopulator::class);

        $this->process();

        self::assertSame(['populator', CustomContractPersonNamePopulator::class], $this->collectedInfoFor('app.populator'));
    }

    public function testIgnoresClassesWithoutAContract(): void
    {
        $this->container->register('app.populator', InvalidCustomContractPopulator::class);

        $this->process();

        self::assertNull($this->collectedInfoFor('app.populator'));
    }

    private function process(): void
    {
        (new DebugInfoPass())->process($this->container);
    }

    /**
     * @return array{string, string}|null the collected type and class for the given service
     */
    private function collectedInfoFor(string $serviceId): ?array
    {
        foreach ($this->container->getDefinition(DebugInfo::class)->getMethodCalls() as [$method, $arguments]) {
            if ('add' !== $method || $serviceId !== $arguments[0]) {
                continue;
            }

            $serviceInfo = $arguments[1];
            \assert($serviceInfo instanceof Definition);

            return [$serviceInfo->getArgument(0), $serviceInfo->getArgument(1)];
        }

        return null;
    }
}
