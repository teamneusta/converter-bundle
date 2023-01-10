<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Exception\PopulationException;

/**
 * @template S of object
 * @template T of object
 * @template U of object
 * @template V of object
 * @template C of object
 * @implements Populator<S, T, C>
 */
class ConverterPopulator implements Populator
{
    /**
     * @param Converter<U, V, C> $converter
     */
    public function __construct(
        private Converter $converter,
        private string    $sourcePropertyName,
        private string    $targetPropertyName,
    )
    {
    }

    /**
     * @throws PopulationException
     */
    public function populate(object $target, object $source, ?object $ctx = null): void
    {
        $sourceValue = null;
        try {
            foreach (['get', 'is', 'has'] as $prefix) {
                if (method_exists($source, $prefix . ucfirst($this->sourcePropertyName))) {
                    $sourceValue = $source->{$prefix . ucfirst($this->sourcePropertyName)}();
                    break;
                }
            }
            $target->{'set' . ucfirst($this->targetPropertyName)}($this->converter->convert($sourceValue));
        } catch (\Throwable $exception) {
            throw new PopulationException($this->sourcePropertyName, $this->targetPropertyName, $exception->getMessage());
        }
    }

}
