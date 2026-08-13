<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Populator;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder;
use Neusta\ConverterBundle\Populator\CustomContractPopulator;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use PHPUnit\Framework\TestCase;

final class CustomContractPopulatorTest extends TestCase
{
    public function testPassesArgumentsInTheOrderOfTheContract(): void
    {
        $received = [];
        $populator = new CustomContractPopulator(
            static function (...$args) use (&$received): void { $received = $args; },
            ParameterOrder::TargetContextSource,
        );

        $source = new User();
        $target = new Person();
        $context = new GenericContext();

        $populator->populate($target, $source, $context);

        self::assertSame([$target, $context, $source], $received);
    }

    public function testOmitsAnUndeclaredContext(): void
    {
        $received = [];
        $populator = new CustomContractPopulator(
            static function (...$args) use (&$received): void { $received = $args; },
            ParameterOrder::SourceTarget,
        );

        $source = new User();
        $target = new Person();

        $populator->populate($target, $source, new GenericContext());

        self::assertSame([$source, $target], $received);
    }

    public function testPopulatesThroughTheGenericPopulatorInterface(): void
    {
        $populator = new CustomContractPopulator(
            static fn (Person $person, User $user) => $person->setFullName($user->getFirstname()),
            ParameterOrder::TargetSource,
        );

        $target = new Person();
        $populator->populate($target, (new User())->setFirstname('Max'));

        self::assertSame('Max', $target->getFullName());
    }
}
