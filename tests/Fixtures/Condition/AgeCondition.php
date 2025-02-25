<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Condition;

class AgeCondition
{
    public function __construct(
        private int $minAge,
    ) {
    }

    public function checkCondition(): \Closure
    {
        return fn ($target, $source, $ctx) => $source->getAgeInYears() >= $this->minAge;
    }
}
