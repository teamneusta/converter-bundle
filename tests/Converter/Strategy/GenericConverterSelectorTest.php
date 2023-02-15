<?php

namespace Neusta\ConverterBundle\Tests\Converter\Strategy;

use Neusta\ConverterBundle\Converter;
use Neusta\ConverterBundle\Converter\Context\GenericContext;
use Neusta\ConverterBundle\Converter\Strategy\ConverterSelector;
use Neusta\ConverterBundle\Converter\Strategy\GenericConverterSelector;
use Neusta\ConverterBundle\Exception\ConverterException;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use function PHPUnit\Framework\assertSame;

class GenericConverterSelectorTest extends TestCase
{
    use ProphecyTrait;

    private GenericContext $ctx;

    private ConverterSelector $selector;

    /** @var Converter|ObjectProphecy */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = $this->prophesize(Converter::class);
        $this->selector = new GenericConverterSelector(
            [
                'converter_1' => $this->converter->reveal(),
            ],
            'selectionKey'
        );
    }

    public function testSelectConverter_find_converter(): void
    {
        $this->ctx = new GenericContext();
        $this->ctx->setValue('selectionKey', 'converter_1');

        assertSame($this->converter->reveal(), $this->selector->selectConverter(new User(), $this->ctx));
    }

    public function testSelectConverter_no_converter_found(): void
    {
        $this->expectException(ConverterException::class);
        $this->expectExceptionMessage('No converter found for key <unknown_converter>');

        $this->ctx = new GenericContext();
        $this->ctx->setValue('selectionKey', 'unknown_converter');

        $this->selector->selectConverter(new User(), $this->ctx);
    }
}
