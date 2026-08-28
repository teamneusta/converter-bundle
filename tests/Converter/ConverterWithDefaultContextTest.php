<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Context;
use Neusta\ConverterBundle\Context\ContextConfigurator;
use Neusta\ConverterBundle\Context\ContextFactory;
use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Converter\ConverterWithDefaultContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContext;
use Neusta\ConverterBundle\Tests\Fixtures\Context\AgeContextConfigurator;
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
        $contextFactory = new ContextFactory([new AgeContextConfigurator()]);

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::that(
            static fn (Context $ctx) => 39 === $ctx->get(AgeContext::class)->age,
        ))->willReturn($person)->shouldBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), $contextFactory);

        self::assertSame($person, $converter->convert($user));
    }

    public function test_convert_builds_the_default_context_freshly_on_every_call(): void
    {
        $user = new User();
        $person = new Person();

        $configurator = new class implements ContextConfigurator {
            public int $calls = 0;

            public function configureContext(Context $ctx): Context
            {
                return $ctx->with(new AgeContext(++$this->calls));
            }
        };
        $contextFactory = new ContextFactory([$configurator]);

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::any())->willReturn($person);

        $converter = new ConverterWithDefaultContext($inner->reveal(), $contextFactory);

        $converter->convert($user);
        $converter->convert($user);

        self::assertSame(2, $configurator->calls);
    }

    public function test_convert_merges_given_context_over_default_context(): void
    {
        $user = new User();
        $person = new Person();
        $contextFactory = new ContextFactory([new AgeContextConfigurator()]);
        $givenContext = Context::create(new AgeContext(22));

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::that(
            static fn (Context $ctx) => 22 === $ctx->get(AgeContext::class)->age,
        ))->willReturn($person)->shouldBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), $contextFactory);

        self::assertSame($person, $converter->convert($user, $givenContext));
    }

    public function test_convert_seeds_context_factory_so_a_configurator_can_skip_redundant_work(): void
    {
        $user = new User();
        $person = new Person();

        $configurator = new class implements ContextConfigurator {
            public int $calls = 0;

            public function configureContext(Context $ctx): Context
            {
                if ($ctx->has(AgeContext::class)) {
                    return $ctx;
                }

                ++$this->calls;

                return $ctx->with(new AgeContext(39));
            }
        };
        $contextFactory = new ContextFactory([$configurator]);
        $givenContext = Context::create(new AgeContext(22));

        $inner = $this->prophesize(Converter::class);
        $inner->convert($user, Argument::that(
            static fn (Context $ctx) => 22 === $ctx->get(AgeContext::class)->age,
        ))->willReturn($person)->shouldBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), $contextFactory);

        self::assertSame($person, $converter->convert($user, $givenContext));
        self::assertSame(0, $configurator->calls);
    }

    public function test_convert_rejects_context_that_is_not_the_new_context_type(): void
    {
        $user = new User();

        $inner = $this->prophesize(Converter::class);
        $inner->convert(Argument::cetera())->shouldNotBeCalled();

        $converter = new ConverterWithDefaultContext($inner->reveal(), new ContextFactory([]));

        $this->expectException(\InvalidArgumentException::class);

        $converter->convert($user, new GenericContext());
    }
}
