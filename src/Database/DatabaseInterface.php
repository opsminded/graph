<?php

declare(strict_types=1);

interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?UserDTO;
    public function getUsers(): array;
    public function insertUser(UserDTO $user): bool;
    public function batchInsertUsers(array $users): bool;
    public function updateUser(UserDTO $user): bool;
    public function deleteUser(string $id): bool;

    public function getCategory(string $id): ?CategoryDTO;
    public function getCategories(): array;
    public function insertCategory(CategoryDTO $category): bool;
    public function updateCategory(CategoryDTO $category): bool;
    public function deleteCategory(string $id): bool;

    public function getType(string $id): ?TypeDTO;
    public function getTypes(): array;
    public function insertType(TypeDTO $type): bool;
    public function updateType(TypeDTO $type): bool;
    public function deleteType(string $id): bool;

    public function getNode(string $id): ?NodeDTO;
    public function getNodes(): array;
    public function insertNode(NodeDTO $node): bool;
    public function batchInsertNodes(array $nodes): bool;
    public function updateNode(NodeDTO $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $id): ?EdgeDTO;
    public function getEdges(): array;
    public function insertEdge(EdgeDTO $edge): bool;
    public function batchInsertEdges(array $edges): bool;
    public function updateEdge(EdgeDTO $edge): bool;
    public function deleteEdge(string $id): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?NodeStatusDTO;
    public function updateNodeStatus(NodeStatusDTO $status): bool;
    public function batchUpdateNodeStatus(array $statuses): bool;

    public function getProject(string $id): ?ProjectDTO;
    public function getProjects(): array;
    public function insertProject(ProjectDTO $project): bool;
    public function updateProject(ProjectDTO $project): bool;
    public function deleteProject(string $id): bool;

    public function getLogs(int $limit): array;
    public function insertLog(LogDTO $log): bool;
}