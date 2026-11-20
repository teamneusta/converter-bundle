<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\DependencyInjection\Compiler;

use Neusta\ConverterBundle\Converter\GenericConverter;
use Neusta\ConverterBundle\Debug\Model\DebugInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceArgumentInfo;
use Neusta\ConverterBundle\DependencyInjection\Compiler\DebugInfoPass;
use Neusta\ConverterBundle\DependencyInjection\Populator\ConvertingPopulatorFactory;
use Neusta\ConverterBundle\Tests\DependencyInjection\NeustaConverterExtensionTestCase;
use Symfony\Component\DependencyInjection\Definition;

class DebugInfoPassInliningTest extends NeustaConverterExtensionTestCase
{
    protected function getPopulatorFactories(): array
    {
        return [
            new ConvertingPopulatorFactory(),
        ];
    }

    /**
     * In the new scheme a converting populator holds its converter inside an inlined
     * `ConverterMapper` definition. Without traversing that definition the debug output would render
     * it as `object(...\Definition)` and the converter → populator edge would vanish from the chart.
     */
    public function test_traverses_inlined_definitions(): void
    {
        $this->load([
            'populators' => [
                'foobar' => [
                    'converting' => [
                        'target' => 'address',
                        'converter' => GenericConverter::class,
                    ],
                ],
            ],
        ]);

        (new DebugInfoPass())->process($this->container);

        $arguments = $this->serviceInfoArgumentsOf('foobar');

        self::assertArrayHasKey('$mapper', $arguments);
        self::assertSame('array', $arguments['$mapper']->getArgument(0), 'the inlined mapper definition must be traversed, not stringified');

        $mapperArguments = $arguments['$mapper']->getArgument(1);
        self::assertSame('reference', $mapperArguments['$converter']->getArgument(0));
        self::assertSame('@' . GenericConverter::class, $mapperArguments['$converter']->getArgument(1));
    }

    /**
     * @return array<string, Definition> the `ServiceArgumentInfo` definitions, keyed by argument name
     */
    private function serviceInfoArgumentsOf(string $serviceId): array
    {
        foreach ($this->container->findDefinition(DebugInfo::class)->getMethodCalls() as [$method, $arguments]) {
            if ('add' !== $method || $serviceId !== $arguments[0]) {
                continue;
            }

            $serviceInfo = $arguments[1];
            self::assertInstanceOf(Definition::class, $serviceInfo);

            return $serviceInfo->getArgument(2);
        }

        self::fail(\sprintf('No debug info was collected for the service "%s".', $serviceId));
    }
}
