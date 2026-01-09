<?php

declare(strict_types=1);

require_once __DIR__ . '/Edge.php';
require_once __DIR__ . '/Node.php';

final class Graph
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