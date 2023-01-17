<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Exception;

class PropertyException extends \Exception
{
    public function __construct(string $propertyName, \Throwable $previous)
    {
        parent::__construct(
            sprintf("Property Exception <%s>: %s", $propertyName, $previous->getMessage()),
            0,
            $previous,
        );
    }
}
