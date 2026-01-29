<?php

declare(strict_types=1);

// Graph DTO for representing nodes and edges in a graph structure.
final class GraphDTO
{
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges
    ) {
    }
}