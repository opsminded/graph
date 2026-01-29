<?php

declare(strict_types=1);

final class Graph
{
    private array $nodes = [];
    private array $edges = [];

    public const KEYNAME_NODES = "nodes";
    public const KEYNAME_EDGES = "edges";

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
        $nodes = [];
        foreach ($this->nodes as $node) {
            $nodes[] = $node->toArray();
        }

        $edges = [];
        foreach ($this->edges as $edge) {
            $edges[] = $edge->toArray();
        }
        return [
            self::KEYNAME_NODES => $nodes,
            self::KEYNAME_EDGES => $edges,
        ];
    }
}