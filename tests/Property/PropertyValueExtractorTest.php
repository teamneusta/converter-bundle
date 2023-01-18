<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Property;

use Neusta\ConverterBundle\Exception\PropertyException;
use Neusta\ConverterBundle\Property\PropertyValueExtractor;
use Neusta\ConverterBundle\Tests\Fixtures\Model\Address;
use Neusta\ConverterBundle\Tests\Fixtures\Model\User;
use PHPUnit\Framework\TestCase;

class PropertyValueExtractorTest extends TestCase
{
    /**
     * @test
     */
    public function testValueExtractingForUser(): void
    {
        $address = (new Address())
            ->setCity('Bremen');
        $user = (new User())
            ->setFirstname('Max')
            ->setUuid(17)
            ->setAddress($address);

        self::assertEquals('Max', PropertyValueExtractor::extractValue($user, 'firstname'));
        self::assertEquals(17, PropertyValueExtractor::extractValue($user, 'uuid'));
        self::assertEquals(
            'Bremen',
            PropertyValueExtractor::extractValue(PropertyValueExtractor::extractValue($user, 'address'), 'city')
        );
    }

    /**
     * @test
     */
    public function testValueExtractingForUser_exceptional_case(): void
    {
        $address = (new Address())
            ->setCity('Bremen');
        $user = (new User())
            ->setFirstname('Max')
            ->setUuid(17)
            ->setAddress($address);

        $this->expectException(PropertyException::class);
        $this->expectExceptionMessageMatches("/Property Exception <street>(.*)/");
        self::assertEquals(
            'Bremen',
            PropertyValueExtractor::extractValue(PropertyValueExtractor::extractValue($user, 'address'), 'street')
        );
    }
}
