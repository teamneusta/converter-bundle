<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Benchmarks\Support;

/**
 * Cycles through a pool of objects across revs instead of reusing one - safe because phpbench runs an iteration's revs against the same instance in a tight loop.
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
