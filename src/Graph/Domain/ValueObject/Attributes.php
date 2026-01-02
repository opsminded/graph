<?php

declare(strict_types=1);

namespace Opsminded\Graph\Graph\Domain\ValueObject;

use ArrayIterator;
use IteratorAggregate;

final class Attributes implements IteratorAggregate
{
    private array $attributes = [];

    public function addAttribute(Attribute $attribute): void
    {
        $this->attributes[] = $attribute;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->attributes);
    }
}