<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\AsPopulatorContract;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Source;
use Neusta\ConverterBundle\Populator\CustomContract\Attribute\Target;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;

/**
 * Adds a second method on top of an inherited contract method - which makes it
 * ambiguous which one populates.
 */
#[AsPopulatorContract]
interface ExtendedMethodCustomContractInterface extends CustomContractPersonPopulatorInterface
{
    public function populateAge(
        #[Source] User $user,
        #[Target] Person $person,
    ): void;
}
