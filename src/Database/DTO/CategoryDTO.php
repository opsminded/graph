<?php

declare(strict_types=1);

// The category of a Node.
// The category influences the shape and size of the Node when rendered.
// It is modeled as a DTO because new categories may be added in the future.
final class CategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $shape,
        public readonly int    $width,
        public readonly int    $height
    ) {
    }
}