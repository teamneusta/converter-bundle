<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Source\User;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Target\Person;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class ConverterWithDefaultContextTest extends TestCase
{
    use ProphecyTrait;

    public function test_convert_injects_default_context_when_none_given(): void
    {
        $user = new User();
        $person = new Person();
        $defaultContext = Context::create(new AgeContext(39));

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::that(
            static fn (Context $ctx) => 39 === $ctx->get(AgeContext::class)->age,
        ))->willReturn($person)->shouldBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), $defaultContext);

        self::assertSame($person, $converter->convert($user));
    }

    public function test_convert_merges_given_context_over_default_context(): void
    {
        $user = new User();
        $person = new Person();
        $defaultContext = Context::create(new AgeContext(39));
        $givenContext = Context::create(new AgeContext(22));

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::that(
            static fn (Context $ctx) => 22 === $ctx->get(AgeContext::class)->age,
        ))->willReturn($person)->shouldBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), $defaultContext);

        self::assertSame($person, $converter->convert($user, $givenContext));
    }

    public function test_convert_rejects_context_that_is_not_the_new_context_type(): void
    {
        $user = new User();

        $inner = $this->prophesize(Converter::class);
        $inner->convert(Argument::cetera())->shouldNotBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), Context::create());

        $this->expectException(\InvalidArgumentException::class);

        $converter->convert($user, new GenericContext());
    }
}
