<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

use ArrayIterator;
use IteratorAggregate;

final class Edges implements IteratorAggregate
{
    private array $edges = [];

    public function addEdge(Edge $edge): void
    {
        $this->edges[] = $edge;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->edges);
    }
}
