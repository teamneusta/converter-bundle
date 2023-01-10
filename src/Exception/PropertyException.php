<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Exception;

use Exception;
use Throwable;

class PropertyException extends Exception
{
    public function __construct(
        ?string    $propertyName = null,
        string     $message = "",
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->message = sprintf("Property Exception <%s>: %s", $propertyName, $this->message);
    }
}