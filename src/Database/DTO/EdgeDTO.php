<?php

declare(strict_types=1);

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