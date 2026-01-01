<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Model\Node;
use Opsminded\Graph\Model\Edge;
use Opsminded\Graph\Repository\GraphRepoInterface;

final class BasicGraphServiceImpl implements GraphServiceInterface
{
    private GraphRepoInterface $repo;

    public function __construct(GraphRepoInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getGraph(): array
    {
        $nodes = $this->repo->getNodes();
        $edges = $this->repo->getEdges();
        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }

    public function getNode(string $id): ?Node
    {
        $row = $this->repo->getNode($id);
        $node = new Node($row['id'], $row['data']);
        return $node;
    }

    public function getNodes(): array
    {
        $rows = $this->repo->getNodes();
        $nodes = [];
        foreach ($rows as $row) {
            $nodes[] = new Node($row['id'], $row['data']);
        }
        return $nodes;
    }

    public function getNodeExists(string $id): bool
    {
        return $this->repo->getNodeExists($id);
    }
    
    public function insertNode(Node $node): bool
    {
        return $this->repo->insertNode($node['id'], $node['data']);
    }
    
    public function updateNode(Node $node): bool
    {
        return $this->repo->updateNode($node['id'], $node['data']);
    }
    
    public function deleteNode(string $id): bool
    {
        return $this->repo->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        return $this->repo->getEdge($source, $target);
    }
    
    public function getEdges(): array
    {
        return $this->repo->getEdges();
    }
    
    public function getEdgeExists(string $source, string $target): bool
    {
        return $this->repo->getEdgeExists($source, $target);
    }
    
    public function insertEdge(Edge $edge): bool
    {
        return $this->repo->insertEdge($edge->getFromNodeId(), $edge->getToNodeId(), $edge->getData());
    }
    
    public function updateEdge(Edge $edge): bool
    {
        return $this->repo->updateEdge($edge->getFromNodeId(), $edge->getToNodeId(), $edge->getData());
    }
    
    public function deleteEdge(string $source, string $target): bool
    {
        return $this->repo->deleteEdge($source, $target);
    }
}
