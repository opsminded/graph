<?php

declare(strict_types=1);

final class GraphDTO
{
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges
    ) {
    }
}