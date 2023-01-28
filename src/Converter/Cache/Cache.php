<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Converter\Cache;

/**
 * @template TSource of object
 * @template TTarget of object
 */
interface Cache
{
    /**
     * @param TSource $source
     *
     * @return TTarget|null
     */
    public function get(object $source): ?object;

    /**
     * @param TSource $source
     * @param TTarget $target
     */
    public function set(object $source, object $target): void;
}
