<?php
namespace Opsminded\Graph;

class Graph
{
    /** @var Node[] */
    protected array $nodes = [];

    /** @var Edge[][] keyed by node id */
    protected array $adj = [];

    public function addNode(Node $node): void
    {
        $id = $node->getId();
        if (!isset($this->nodes[$id])) {
            $this->nodes[$id] = $node;
            $this->adj[$id] = [];
        }
    }

    public function getNode(string $id): ?Node
    {
        return $this->nodes[$id] ?? null;
    }

    public function addEdge(Edge $edge): void
    {
        $from = $edge->from()->getId();
        $to = $edge->to()->getId();
        $this->addNode($edge->from());
        $this->addNode($edge->to());
        $this->adj[$from][] = $edge;
    }

    /** @return Edge[] */
    public function neighbors(string $id): array
    {
        return $this->adj[$id] ?? [];
    }

    /** @return Node[] */
    public function nodes(): array
    {
        return array_values($this->nodes);
    }
}
