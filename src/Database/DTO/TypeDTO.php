<?php

declare(strict_types=1);

final class TypeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }
}