<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract\Attribute;

/**
 * Marks an interface as a custom populator contract.
 *
 * The interface must declare exactly one method with exactly one {@see Source}
 * and one {@see Target} parameter and at most one (nullable) {@see Context}
 * parameter. The parameters may be declared in any order.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsPopulatorContract
{
}
