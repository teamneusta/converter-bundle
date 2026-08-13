<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

/**
 * Extends a contract without being annotated itself - must not be treated as a
 * second contract.
 */
interface InheritingCustomContractInterface extends CustomContractPersonPopulatorInterface
{
}
