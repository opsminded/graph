<?php

declare(strict_types=1);

final class ProjectDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $author,
        public readonly array $data,
    ) {
    }
}