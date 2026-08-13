<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
final class DefinitionReflector
{
    /**
     * Resolves the class of a definition - following the parent chain of a
     * {@see ChildDefinition} that does not declare a class on its own - and
     * returns its reflection.
     *
     * Returns `null` if the class cannot be determined or does not exist.
     */
    public static function reflect(ContainerBuilder $container, Definition $definition): ?\ReflectionClass
    {
        while ((null === $class = $definition->getClass()) && $definition instanceof ChildDefinition) {
            if (!$parentId = $definition->getParent()) {
                break;
            }

            $definition = $container->findDefinition($parentId);
        }

        if (null === $class) {
            return null;
        }

        return $container->getReflectionClass($class, false);
    }
}
