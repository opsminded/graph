<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Model\Node;
use Opsminded\Graph\Model\Nodes;
use Opsminded\Graph\Model\Edge;
use Opsminded\Graph\Model\Edges;
use Opsminded\Graph\Model\Graph;
use Opsminded\Graph\Repository\GraphRepoInterface;

final class BasicGraphServiceImpl implements GraphServiceInterface
{
    private GraphRepoInterface $repo;

    public function __construct(GraphRepoInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getGraph(): Graph
    {
        $nodes = $this->repo->getNodes();
        $edges = $this->repo->getEdges();

        $graph = new Graph($nodes, $edges);

        return $graph;
    }

    public function getNode(string $id): ?Node
    {
        $row  = $this->repo->getNode($id);
        if ($row === null) {
            return null;
        }
        $node = new Node($row['data']['id'], $row['data']['label'], $row['data']['category'], $row['data']['type'], $row['data']);
        return $node;
    }

    public function getNodes(): Nodes
    {
        $rows  = $this->repo->getNodes();
        $nodes = new Nodes();
        foreach ($rows as $row) {
            $nodes->addNode(new Node($row['data']['id'], $row['data']['label'], $row['data']['category'], $row['data']['type'], $row['data']));
        }
        return $nodes;
    }

    public function getNodeExists(string $id): bool
    {
        return $this->repo->getNodeExists($id);
    }

    public function insertNode(Node $node): bool
    {
        return $this->repo->insertNode(
            $node->getId(),
            $node->getLabel(),
            $node->getCategory(),
            $node->getType(),
            $node->getData()
        );
    }

    public function updateNode(Node $node): bool
    {
        return $this->repo->updateNode(
            $node->getId(),
            $node->getLabel(),
            $node->getCategory(),
            $node->getType(),
            $node->getData()
        );
    }

    public function deleteNode(string $id): bool
    {
        return $this->repo->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        $id = $source . '@' . $target;
        $row = $this->repo->getEdge($id);
        if ($row === null) {
            return null;
        }
        return new Edge($row['data']['id'], $row['data']['source'], $row['data']['target'], $row['data']);
    }

    public function getEdges(): Edges
    {
        $rows  = $this->repo->getEdges();
        $edges = new Edges();
        foreach ($rows as $row) {
            $edges->addEdge(new Edge($row['data']['id'], $row['data']['source'], $row['data']['target'], $row['data']));
        }
        return $edges;
    }

    public function getEdgeExists(string $source, string $target): bool
    {
        return $this->repo->getEdgeExistsByNodes($source, $target);
    }

    public function insertEdge(Edge $edge): bool
    {
        $id = $edge->getSourceNodeId() . '@' . $edge->getTargetNodeId();
        return $this->repo->insertEdge($id, $edge->getSourceNodeId(), $edge->getTargetNodeId(), $edge->getData());
    }

    public function updateEdge(Edge $edge): bool
    {
        $id = $edge->getSourceNodeId() . '@' . $edge->getTargetNodeId();
        return $this->repo->updateEdge($id, $edge->getSourceNodeId(), $edge->getTargetNodeId(), $edge->getData());
    }

    public function deleteEdge(string $source, string $target): bool
    {
        $id = $source . '@' . $target;
        return $this->repo->deleteEdge($id);
    }
}
