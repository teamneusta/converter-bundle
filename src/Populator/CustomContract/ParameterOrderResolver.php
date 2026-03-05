<?php
declare(strict_types=1);

namespace Neusta\ConverterBundle\Populator\CustomContract;

/**
 * @internal
 */
final class ParameterOrderResolver
{
    private function __construct() {}

    /**
     * Todo: can we cache this for known classes!?
     */
    public static function resolve(\ReflectionMethod $method): ParameterOrder
    {
        $parameterOrder = [];
        foreach ($method->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(Source::class)) {
                $parameterOrder[] = 'source';
            } elseif ([] !== $parameter->getAttributes(Target::class)) {
                $parameterOrder[] = 'target';
            } elseif ([] !== $parameter->getAttributes(Context::class)) {
                // Todo: wenn es das gibt, dann muss es vom Typ `GenericContext|null` sein und einen default Wert haben!
                $parameterOrder[] = 'context';
            } else {
                throw new \LogicException(sprintf(
                    'Parameter "$%s" of method "%s::%s" must be annotated with #[Source], #[Target] or #[Context].',
                    $parameter->getName(),
                    $method->getDeclaringClass()->getName(),
                    $method->getName(),
                ));
            }
        }

        if (!\in_array('source', $parameterOrder, true) || !\in_array('target', $parameterOrder, true)) {
            throw new \LogicException(sprintf(
                'Method "%s::%s" must have parameters annotated with both #[Source] and #[Target].',
                $method->getDeclaringClass()->getName(),
                $method->getName(),
            ));
        }

        return ParameterOrder::fromArray($parameterOrder);
    }
}
