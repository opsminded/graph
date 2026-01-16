<?php

declare(strict_types=1);

final class ModelGraph
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
        return [
            self::KEYNAME_NODES => $this->nodes,
            self::KEYNAME_EDGES => $this->edges,
        ];
    }
}