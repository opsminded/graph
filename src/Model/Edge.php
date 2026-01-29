<?php

declare(strict_types=1);

final class Edge
{
    private string $id;
    private string $label;
    private string $source;
    private string $target;
    private array  $data;

    public function __construct(string $source, string $target, string $label, array $data = [])
    {
        $this->id     = "{$source}-{$target}";
        $this->source = $source;
        $this->target = $target;
        $this->label  = $label;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}