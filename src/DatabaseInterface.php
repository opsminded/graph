<?php

declare(strict_types=1);

interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): bool;
    public function updateUser(string $id, string $group): bool;

    public function getCategories(): array;
    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool;
    
    public function getTypes(): array;
    public function insertType(string $id, string $name): bool;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?array;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, string $label, array $data = []): bool;
    public function updateEdge(string $id, string $source, string $target, string $label, array $data = []): bool;
    public function deleteEdge(string $id): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?array;
    public function updateNodeStatus(string $id, string $status): bool;

    public function getLogs(int $limit): array;
    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool;
}