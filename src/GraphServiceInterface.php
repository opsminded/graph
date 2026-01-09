<?php

declare(strict_types=1);

interface GraphServiceInterface
{
    public function getUser(string $id): ?ModelUser;
    public function insertUser(ModelUser $user): void;
    public function updateUser(ModelUser $user): void;

    public function getGraph(): ModelGraph;

    public function getNode(string $id): ?ModelNode;
    public function getNodes(): array;
    public function insertNode(ModelNode $node): void;
    public function updateNode(ModelNode $node): void;
    public function deleteNode(string $id): void;

    public function getEdge(string $source, string $target): ?ModelEdge;
    public function getEdges(): array;
    public function insertEdge(ModelEdge $edge): void;
    public function updateEdge(ModelEdge $edge): void;
    public function deleteEdge(ModelEdge $edge): void;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ModelStatus;
    public function updateNodeStatus(ModelStatus $status): void;

    public function getLogs($limit): array;
}