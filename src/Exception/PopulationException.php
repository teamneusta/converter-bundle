<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Exception;

use Exception;
use Throwable;

class PopulationException extends Exception
{
    public function __construct(
        private ?string $sourcePropertyName = null,
        private ?string $targetPropertyName = null,
        string          $message = "",
        int             $code = 0,
        ?Throwable      $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->message = sprintf("Population Exception (%s -> %s): %s",
            $this->sourcePropertyName,
            $this->targetPropertyName,
            $this->message
        );
    }
}