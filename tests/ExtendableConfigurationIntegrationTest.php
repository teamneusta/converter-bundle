<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests;

use Neusta\ConverterBundle\Tests\Fixtures\Bundle\ExtendingBundle\ExtendingBundle;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use Neusta\ConverterBundle\Tests\Support\Attribute\ConfigureContainer;
use Neusta\ConverterBundle\Tests\Support\Attribute\RegisterBundle;

/**
 * Acceptance test for the extension point: another bundle registers its own converter and populator
 * types from its `build()` method, and both become configurable under `neusta_converter`.
 *
 * @see https://github.com/teamneusta/converter-bundle/issues/79
 */
#[RegisterBundle(ExtendingBundle::class)]
#[ConfigureContainer(__DIR__ . '/Fixtures/Config/extending_bundle.yaml')]
class ExtendableConfigurationIntegrationTest extends ConfigurableKernelTestCase
{
    public function test_converter_type_from_another_bundle(): void
    {
        $source = (new User())->setFullName('Max Mustermann');

        $target = self::getContainer()->get('test.reversing.converter')->convert($source);

        self::assertInstanceOf(Person::class, $target);
        self::assertSame('nnamretsuM xaM', $target->getFullName());
    }

    public function test_populator_type_from_another_bundle_as_a_property_type(): void
    {
        $source = (new User())->setFullName('  Max Mustermann  ');

        $target = self::getContainer()->get('test.uppercase.converter')->convert($source);

        self::assertInstanceOf(Person::class, $target);
        self::assertSame('MAX MUSTERMANN', $target->getFullName());
    }

    public function test_populator_type_from_another_bundle_as_a_standalone_populator(): void
    {
        $source = (new User())->setFullName('Max Mustermann');
        $target = new Person();

        self::getContainer()->get('test.uppercase.populator')->populate($target, $source);

        self::assertSame('MAX MUSTERMANN', $target->getFullName());
    }
}
