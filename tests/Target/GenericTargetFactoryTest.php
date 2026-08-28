<?php declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Target;

use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GenericTargetFactoryTest extends TestCase
{
    #[Test]
    public function create_regular_case(): void
    {
        $factory = new GenericTargetFactory(TestTarget::class);

        self::assertInstanceOf(TestTarget::class, $factory->create());
    }

    #[Test]
    public function create_with_non_instantiable_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Target class "%s" is not instantiable',
            TestNonInstantiableTarget::class,
        ));

        new GenericTargetFactory(TestNonInstantiableTarget::class);
    }

    #[Test]
    public function create_with_constructor_params_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Target class "%s" has required constructor parameters',
            TestWithConstructorParamsTarget::class,
        ));

        new GenericTargetFactory(TestWithConstructorParamsTarget::class);
    }
}

class TestTarget
{
}

abstract class TestNonInstantiableTarget
{
}

class TestWithConstructorParamsTarget
{
    public function __construct($value)
    {
    }
}
