<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Debug;

use Neusta\ConverterBundle\Debug\Model\ServiceArgumentInfo;
use Neusta\ConverterBundle\Debug\Model\ServiceInfo;
use PHPUnit\Framework\TestCase;

final class ServiceInfoTest extends TestCase
{
    public function test_collects_direct_references(): void
    {
        $info = new ServiceInfo('populator', 'MyPopulator', [
            '$accessor' => new ServiceArgumentInfo('reference', '@property_accessor'),
            '$targetProperty' => new ServiceArgumentInfo('scalar', 'address'),
        ]);

        self::assertSame(['property_accessor'], array_values($info->getReferences()));
    }

    public function test_collects_references_from_a_nested_array(): void
    {
        $info = new ServiceInfo('converter', 'MyConverter', [
            '$populators' => new ServiceArgumentInfo('array', [
                new ServiceArgumentInfo('reference', '@populator_a'),
                new ServiceArgumentInfo('reference', '@populator_b'),
            ]),
        ]);

        self::assertSame(['populator_a', 'populator_b'], array_values($info->getReferences()));
    }

    /**
     * A converting populator holds its converter inside an inlined mapper definition, which in turn
     * may hold another mapper definition. Without deep traversal the converter → populator edge
     * would be missing from the dependency chart.
     */
    public function test_collects_references_from_deeply_nested_definitions(): void
    {
        $info = new ServiceInfo('populator', 'PropertyMappingPopulator', [
            '$mapper' => new ServiceArgumentInfo('array', [
                'class' => new ServiceArgumentInfo('scalar', 'ArrayPropertyMapper'),
                '$mapper' => new ServiceArgumentInfo('array', [
                    'class' => new ServiceArgumentInfo('scalar', 'ConverterMapper'),
                    '$converter' => new ServiceArgumentInfo('reference', '@my.inner.converter'),
                ]),
            ]),
            '$accessor' => new ServiceArgumentInfo('reference', '@property_accessor'),
        ]);

        self::assertSame(
            ['my.inner.converter', 'property_accessor'],
            array_values($info->getReferences()),
        );
    }
}
