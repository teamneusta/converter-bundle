<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Fixtures\Context;

/**
 * A plain object that is neither Context nor GenericContext - used to isolate the per-call
 * trigger_deprecation() that GenericConverter::convert() gained in #102 for exactly this case.
 */
final class OpaqueContext
{
}
