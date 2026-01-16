<?php

declare(strict_types=1);

interface ServiceInterface
{
    public function getUser(string $id): ?ModelUser;
    public function insertUser(ModelUser $user): bool;
    public function updateUser(ModelUser $user): bool;

    public function getCategories(): array;
    public function insertCategory(ModelCategory $category): bool;
    
    public function getTypes(): array;
    public function insertType(ModelType $type): bool;

    public function getGraph(): ModelGraph;

    public function getNode(string $id): ?ModelNode;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?ModelNode;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(ModelNode $node): bool;
    public function updateNode(ModelNode $node): bool;
    public function deleteNode(ModelNode $node): bool;

    public function getEdge(string $source, string $target): ?ModelEdge;
    public function getEdges(): array;
    public function insertEdge(ModelEdge $edge): bool;
    public function updateEdge(ModelEdge $edge): bool;
    public function deleteEdge(ModelEdge $edge): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?ModelStatus;
    public function updateNodeStatus(ModelStatus $status): bool;

    public function getLogs(int $limit): array;
}