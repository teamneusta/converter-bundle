<?php

declare(strict_types=1);

namespace Neusta\ConverterBundle\Tests\Fixtures\Model;

/**
 * A minimal self-referential fixture for tests that need arbitrarily deep/wide nesting without
 * pulling in the shape of the User/Person fixtures.
 */
final class RecursiveNode
{
    public ?self $child = null;

    /** @var list<self> */
    public array $items = [];
}
