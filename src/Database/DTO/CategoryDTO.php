<?php

declare(strict_types=1);

final class CategoryDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $shape,
        public int    $width,
        public int    $height
    ) {
    }
}