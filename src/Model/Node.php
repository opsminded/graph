<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

final class Node
{
    private string $id;
    private array $data = [];

    public function __construct(string $id, array $data)
    {
        $this->id   = $id;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
