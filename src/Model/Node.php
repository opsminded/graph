<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

final class Node
{
    private string $id;
    private string $label;
    private string $category;
    private string $type;

    private array $data = [];

    public function __construct(string $id, string $label, string $category, string $type, array $data)
    {
        $this->id       = $id;
        $this->label    = $label;
        $this->category = $category;
        $this->type     = $type;
        $this->data     = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
