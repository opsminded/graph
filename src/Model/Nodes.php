<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

use ArrayIterator;
use IteratorAggregate;

final class Nodes implements IteratorAggregate
{
    private array $nodes = [];

    public function addNode(Node $node): void
    {
        $this->nodes[] = $node;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->nodes);
    }
}
