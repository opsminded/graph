<?php

declare(strict_types=1);

interface GraphDatabaseInterface
{
    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): void;
    public function updateUser(string $id, string $group): void;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): void;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): void;
    public function deleteNode(string $id): void;

    public function getEdge(string $source, string $target): ?array;
    public function getEdgeById(string $id): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, array $data = []): void;
    public function updateEdge(string $id, string $source, string $target, array $data = []): void;
    public function deleteEdge(string $id): void;

    public function getStatus(): array;
    public function getNodeStatus(string $id): array;
    public function updateNodeStatus(string $id, string $status): void;

    public function getLogs(int $limit): array;
    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): void;
}