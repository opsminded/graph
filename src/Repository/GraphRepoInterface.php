<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

interface GraphRepoInterface
{
    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function getNodeExists(string $id): bool;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $id): ?array;
    public function getEdges(): array;
    public function getEdgeExistsById(string $id): bool;
    public function getEdgeExistsByNodes(string $source, string $target): bool;
    public function insertEdge(string $id, string $source, string $target, array $data = []): bool;
    public function updateEdge(string $id, string $source, string $target, array $data = []): bool;
    public function deleteEdge(string $id): bool;
}
