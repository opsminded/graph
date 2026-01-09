<?php

declare(strict_types=1);

interface GraphServiceInterface
{
    public function getUser(string $id): ?User;
    public function insertUser(User $user): void;
    public function updateUser(User $user): void;

    public function getGraph(): Graph;

    public function getNode(string $id): ?Node;
    public function getNodes(): array;
    public function insertNode(Node $node): void;
    public function updateNode(Node $node): void;
    public function deleteNode(string $id): void;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): array;
    public function insertEdge(Edge $edge): void;
    public function updateEdge(Edge $edge): void;
    public function deleteEdge(Edge $edge): void;

    public function getStatus(): array;
    public function getNodeStatus(string $id): Status;
    public function updateNodeStatus(Status $status): void;

    public function getLogs($limit): array;
}