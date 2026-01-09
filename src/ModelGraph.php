<?php

declare(strict_types=1);

final class ModelGraph
{
    private array $nodes = [];
    private array $edges = [];

    public function __construct(array $nodes, array $edges)
    {
        $this->nodes = $nodes;
        $this->edges = $edges;
    }
    
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getEdges(): array
    {
        return $this->edges;
    }

    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
        ];
    }
}