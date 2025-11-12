<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Context;

use Neusta\ConverterBundle\Context;

/**
 * This interface can be implemented by services that will configure the initial context.
 *
 * Example:
 *
 * ```php
 * use Neusta\ConverterBundle\Context;
 * use Neusta\ConverterBundle\Context\ContextConfigurator;
 *
 * final class MyContextConfigurator implements ContextConfigurator
 * {
 *     public function __construct(
 *         private readonly string $someValue,
 *     ) {}
 *
 *     public function configureContext(Context $ctx): Context
 *     {
 *         return $ctx->with(
 *             new MyContext($this->someValue),
 *         );
 *     }
 * }
 * ```
 *
 * If you want it to be globally available,
 * you can configure it via `neusta_converter.context_configurators`, e.g.
 *
 * ```yaml
 * neusta_converter:
 *  context_configurators:
 *    - GlobalContextConfigurator
 * ```
 *
 * If you want it to be available only for a specific converter,
 * you can configure it using the `context_configurators` option of the respective converter, e.g.
 *
 * ```yaml
 * neusta_converter:
 *  converter:
 *    my_converter:
 *      context_configurators:
 *        - MyContextConfigurator
 * ```
 */
interface ContextConfigurator
{
    public function configureContext(Context $ctx): Context;
}
