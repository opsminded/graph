<?php

declare(strict_types=1);

namespace Opsminded\Graph\Graph\Domain\Entity;

final class Graph
{
    private array $nodes = [];
    private array $edges = [];
    private array $data  = [];

    public function __construct(array $nodes, array $edges, array $data = [])
    {
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->data  = $data;
    }

    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getEdges(): array
    {
        return $this->edges;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'data'  => $this->data,
        ];
    }
}
