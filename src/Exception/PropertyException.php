<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Exception;

class PropertyException extends \Exception
{
    public function __construct(string $propertyName, ?string $message = null, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Property Exception <%s>: %s", $propertyName, $message ?? $previous?->getMessage() ?? ''),
            0,
            $previous,
        );
    }
}
