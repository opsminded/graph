<?php

declare(strict_types=1);

// The type of a Node.
// The type influences the icon displayed for the Node.
// It is modeled as a DTO because new types may be added in the future.
final class TypeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }
}