<?php

declare(strict_types=1);

interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?array;
    public function getUsers(): array;
    public function insertUser(string $id, string $group): bool;
    public function batchInsertUsers(array $users): bool;
    public function updateUser(string $id, string $group): bool;
    public function deleteUser(string $id): bool;

    public function getCategory(string $id): ?array;
    public function getCategories(): array;
    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool;
    public function updateCategory(string $id, string $name, string $shape, int $width, int $height): bool;
    public function deleteCategory(string $id): bool;

    public function getType(string $id): ?array;
    public function getTypes(): array;
    public function insertType(string $id, string $name): bool;
    public function updateType(string $id, string $name): bool;
    public function deleteType(string $id): bool;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function insertNode(string $id, string $label, string $category, string $type, bool $userCreated = false, array $data = []): bool;
    public function batchInsertNodes(array $nodes): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, string $label, array $data = []): bool;
    public function batchInsertEdges(array $edges): bool;
    public function updateEdge(string $id, string $label, array $data = []): bool;
    public function deleteEdge(string $id): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?array;
    public function updateNodeStatus(string $id, string $status): bool;
    public function batchUpdateNodeStatus(array $statuses): bool;

    public function getProject(string $id): ?array;
    public function getProjects(): array;
    public function insertProject(string $id, string $name, string $author, array $data): bool;
    public function updateProject(string $id, string $name, string $author, array $data): bool;
    public function deleteProject(string $id): bool;

    public function getSuccessors(string $id): array;

    public function getLogs(int $limit): array;
    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool;
}