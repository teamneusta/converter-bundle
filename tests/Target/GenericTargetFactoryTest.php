<?php declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Target;

use Neusta\ConverterBundle\Target\GenericTargetFactory;
use PHPUnit\Framework\TestCase;

class GenericTargetFactoryTest extends TestCase
{
    private GenericTargetFactory $factory;

    /**
     * @test
     */
    public function create_regular_case(): void
    {
        $this->factory = new GenericTargetFactory(TestTarget::class);
        $result = $this->factory->create();

        self::assertSame(1, $result->getValue());
    }

    /**
     * @test
     */
    public function create_with_non_instnatiable_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Neusta\\ConverterBundle\\Tests\\Target\\TestNonInstantiableTarget" is not instantiable.');
        $this->factory = new GenericTargetFactory(TestNonInstantiableTarget::class);
    }

    /**
     * @test
     */
    public function create_with_constructor_params_case(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Neusta\\ConverterBundle\\Tests\\Target\\TestWithConstructorParamsTarget" has required constructor parameters');
        $this->factory = new GenericTargetFactory(TestWithConstructorParamsTarget::class);
    }
}

class TestTarget
{
    private int $value;

    public function __construct()
    {
        $this->value = 1;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}

class TestNonInstantiableTarget
{
    private int $value;

    private function __construct()
    {
        $this->value = 1;
    }
}

class TestWithConstructorParamsTarget
{
    public function __construct(
        private int $value,
    ) {
    }
}
