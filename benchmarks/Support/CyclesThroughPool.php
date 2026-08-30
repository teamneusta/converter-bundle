<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Support;

/**
 * Cycles sequentially through a fixed pool of objects across revolutions. phpbench calls the same
 * subject instance `revs` times in a tight loop before the next #[BeforeMethods] run, so a mutable
 * counter correctly varies the input across revs instead of reusing the same one at every call -
 * needed to measure a cache-miss path realistically (see #35, CachingConverter).
 */
trait CyclesThroughPool
{
    /** @var list<object> */
    private array $pool;
    private int $poolIndex = 0;

    /**
     * @param list<object> $pool
     */
    private function setPool(array $pool): void
    {
        $this->pool = $pool;
        $this->poolIndex = 0;
    }

    private function nextFromPool(): object
    {
        $value = $this->pool[$this->poolIndex];
        $this->poolIndex = ($this->poolIndex + 1) % \count($this->pool);

        return $value;
    }
}
