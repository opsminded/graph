<?php

declare(strict_types=1);

// Edge DTO join two nodes in a graph structure.
// It contains identifiers for the source and target nodes, a label for the edge, and optional metadata.
final class EdgeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $target,
        public readonly string $label,
        public readonly array $data = []
    ) {
    }
}