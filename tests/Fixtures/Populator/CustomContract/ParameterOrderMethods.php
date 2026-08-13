<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * Carries method signatures for {@see \Neusta\ConverterBundle\Populator\CustomContract\ParameterOrder}
 * tests. Deliberately *not* a populator contract - it is only reflected upon,
 * never implemented.
 */
interface ParameterOrderMethods
{
    public function sourceTargetContext(
        #[Source] User $user,
        #[Target] Person $person,
        #[Context] ?GenericContext $context,
    ): void;

    public function targetContextSource(
        #[Target] Person $person,
        #[Context] ?GenericContext $context,
        #[Source] User $user,
    ): void;

    public function sourceTarget(
        #[Source] User $user,
        #[Target] Person $person,
    ): void;

    public function targetSource(
        #[Target] Person $person,
        #[Source] User $user,
    ): void;

    public function nonNullableContext(
        #[Source] User $user,
        #[Target] Person $person,
        #[Context] GenericContext $context,
    ): void;

    public function multipleRoles(
        #[Source] #[Target] User $user,
        #[Target] Person $person,
    ): void;

    public function unannotated(
        User $user,
        #[Target] Person $person,
    ): void;

    public function twoSources(
        #[Source] User $first,
        #[Source] User $second,
        #[Target] Person $person,
    ): void;

    public function missingTarget(
        #[Source] User $user,
    ): void;
}
