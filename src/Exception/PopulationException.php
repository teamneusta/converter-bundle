<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Exception;

class PopulationException extends \Exception
{
    public function __construct(string $sourcePropertyName, string $targetPropertyName, \Throwable $previous)
    {
        parent::__construct(
            \sprintf('Population Exception (%s -> %s): %s',
                $sourcePropertyName,
                $targetPropertyName,
                $previous->getMessage(),
            ),
            0,
            $previous,
        );
    }
}
