<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

interface GraphRepoInterface
{
    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function getNodeExists(string $id): bool;
    public function insertNode(string $id, array $data): bool;
    public function updateNode(string $id, array $data): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdges(): array;
    public function getEdgeExists(string $source, string $target): bool;
    public function insertEdge(string $source, string $target, array $data = []): bool;
    public function updateEdge(string $source, string $target, array $data = []): bool;
    public function deleteEdge(string $source, string $target): bool;
}
