<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Populator\CustomContract;

use Neusta\ConverterBundle\Populator\CustomContract\Attribute\AsPopulatorContract;

/**
 * Extends an annotated contract *and* carries the attribute itself - the more
 * specific one wins instead of counting as two contracts.
 */
#[AsPopulatorContract]
interface DoubleAnnotatedCustomContractInterface extends CustomContractPersonPopulatorInterface
{
}
