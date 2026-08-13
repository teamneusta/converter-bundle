<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\AsPopulatorContract;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Context;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * Declares its parameters in an unusual order to prove that the roles - not the
 * positions - decide which argument goes where.
 */
#[AsPopulatorContract]
interface ReorderedCustomContractInterface
{
    public function populateName(
        #[Target] Person $person,
        #[Context] ?GenericContext $context,
        #[Source] User $user,
    ): void;
}
