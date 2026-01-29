<?php

declare(strict_types=1);

final class NodeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $category,
        public readonly string $type,
        public readonly array $data
    ) {
    }
}