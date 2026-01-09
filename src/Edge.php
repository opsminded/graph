<?php

declare(strict_types=1);

final class Edge
{
    private string $source;
    private string $target;
    private array  $data;

    public function __construct(string $source, string $target, array $data = [])
    {
        $this->source = $source;
        $this->target = $target;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return "{$this->source}-{$this->target}";
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
        return [
            'source' => $this->source,
            'target' => $this->target,
            'data'   => $this->data
        ];
    }
}