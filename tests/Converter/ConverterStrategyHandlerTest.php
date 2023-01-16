<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Converter;

use Neusta\ConverterBundle\Converter\Converter;
use Neusta\ConverterBundle\Converter\ConverterStrategyHandler;
use Neusta\ConverterBundle\Converter\DefaultConverterContext;
use Neusta\ConverterBundle\Exception\ConverterException;
use Neusta\ConverterBundle\Strategy\ConverterSelector;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Person;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class ConverterStrategyHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ConverterStrategyHandler<User, Person, DefaultConverterContext>
     */
    private ConverterStrategyHandler $strategyHandler;

    /** @var ObjectProphecy<Converter> */
    private $converter;
    /** @var ObjectProphecy<ConverterSelector> */
    private $selector;

    public function testConvert_regular_case(): void
    {
        $this->selector = $this->prophesize(ConverterSelector::class);
        $this->converter = $this->prophesize(Converter::class);

        $this->strategyHandler = new ConverterStrategyHandler(
            [
                'testKey' => $this->converter->reveal(),
            ],
            $this->selector->reveal(),
        );

        $user = new User();
        $person = new Person();

        $this->selector->selectConverter($user, null)->willReturn('testKey');
        $this->converter->convert($user, null)->willReturn($person)->shouldBeCalled();

        $this->strategyHandler->convert($user);

    }

    public function testConvert_exceptional_case(): void
    {
        $this->selector = $this->prophesize(ConverterSelector::class);
        $this->converter = $this->prophesize(Converter::class);

        $this->strategyHandler = new ConverterStrategyHandler(
            [
                'testKey' => $this->converter->reveal(),
            ],
            $this->selector->reveal(),
        );

        $user = new User();
        $person = new Person();

        $this->selector->selectConverter($user, null)->willReturn('otherKey');
        $this->converter->convert($user, null)->willReturn($person)->shouldNotBeCalled();

        $this->expectException(ConverterException::class);
        $this->expectExceptionMessage("No converter found for key <otherKey>");

        $this->strategyHandler->convert($user);

    }

}
