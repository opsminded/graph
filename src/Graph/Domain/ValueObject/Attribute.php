<?php

declare(strict_types=1);

namespace Opsminded\Graph\Graph\Domain\ValueObject;

final class Attribute
{
    private function __construct(
        private readonly string $name,
        private readonly mixed $value,
    ) {
    }

    public static function create(string $name, mixed $value): self
    {
        return new self($name, $value);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}