<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Property;

use Neusta\ConverterBundle\Exception\PropertyException;

class PropertyValueExtractor
{
    /**
     * @throws PropertyException
     */
    public static function extractValue(object $source, string $propertyName): mixed
    {
        try {
            foreach (['get', 'is', 'has'] as $prefix) {
                if (method_exists($source, $prefix . ucfirst($propertyName))) {
                    return $source->{$prefix . ucfirst($propertyName)}();
                }
            }
        } catch (\Throwable $exception) {
            throw new PropertyException($propertyName, previous: $exception);
        }

        throw new PropertyException($propertyName, 'no accessor found');
    }
}
