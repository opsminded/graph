<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

use ArrayIterator;
use IteratorAggregate;

final class Statuses implements IteratorAggregate
{
    private array $statuses = [];

    public function addStatus(Status $status): void
    {
        $this->statuses[] = $status;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->statuses);
    }
}
