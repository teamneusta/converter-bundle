<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection\Populator;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @experimental This interface is not covered by the backward compatibility promise yet. Its shape
 *               will be settled once the first real consumers (see #39, #79) have exercised it -
 *               most likely by replacing the loose parameters with a factory context object (#108).
 */
interface PropertyPopulatorFactory
{
    /**
     * Adds the type-specific options. Called for both entry points: the node under
     * `neusta_converter.converters.<id>.generic.properties.<target>.<type>.` and the one under
     * `neusta_converter.populators.<id>.<type>.`.
     */
    public function addPropertyConfiguration(ArrayNodeDefinition $node): void;

    /**
     * Whether the shared `default` option of the `properties` shorthand applies to this type - e.g.
     * a mapper that discards a scalar default (like `ArrayPropertyMapper`) must reject it.
     */
    public function supportsDefaultValue(): bool;
}
