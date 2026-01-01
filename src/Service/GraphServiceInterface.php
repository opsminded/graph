<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Model\Node;
use Opsminded\Graph\Model\Edge;

interface GraphServiceInterface
{
    public function getGraph(): array;

    public function getNode(string $id): ?Node;
    public function getNodes(): array;
    public function getNodeExists(string $id): bool;
    public function insertNode(Node $node): bool;
    public function updateNode(Node $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): array;
    public function getEdgeExists(string $source, string $target): bool;
    public function insertEdge(Edge $edge): bool;
    public function updateEdge(Edge $edge): bool;
    public function deleteEdge(string $source, string $target): bool;
}
