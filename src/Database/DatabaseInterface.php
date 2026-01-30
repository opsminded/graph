<?php

declare(strict_types=1);

interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?UserDTO;

    /**
     * @return UserDTO[]
     */
    public function getUsers(): array;

    public function insertUser(UserDTO $user): bool;

    /**
     * @param UserDTO[] $users
     */
    public function batchInsertUsers(array $users): bool;

    public function updateUser(UserDTO $user): bool;
    public function deleteUser(string $id): bool;

    public function getCategory(string $id): ?CategoryDTO;

    /**
     * @return CategoryDTO[]
     */
    public function getCategories(): array;

    public function insertCategory(CategoryDTO $category): bool;
    public function updateCategory(CategoryDTO $category): bool;
    public function deleteCategory(string $id): bool;

    public function getType(string $id): ?TypeDTO;

    /**
     * @return TypeDTO[]
     */
    public function getTypes(): array;

    public function insertType(TypeDTO $type): bool;
    public function updateType(TypeDTO $type): bool;
    public function deleteType(string $id): bool;

    public function getNode(string $id): ?NodeDTO;

    /**
     * @return NodeDTO[]
     */
    public function getNodes(): array;

    public function insertNode(NodeDTO $node): bool;

    /**
     * @param NodeDTO[] $nodes
     */
    public function batchInsertNodes(array $nodes): bool;

    public function updateNode(NodeDTO $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $id): ?EdgeDTO;

    /**
     * @return EdgeDTO[]
     */
    public function getEdges(): array;

    public function insertEdge(EdgeDTO $edge): bool;

    /**
     * @param EdgeDTO[] $edges
     */
    public function batchInsertEdges(array $edges): bool;

    public function updateEdge(EdgeDTO $edge): bool;
    public function deleteEdge(string $id): bool;

    public function getNodeStatus(string $id): ?StatusDTO;
    public function updateNodeStatus(StatusDTO $status): bool;

    /**
     * @param StatusDTO[] $statuses
     */
    public function batchUpdateNodeStatus(array $statuses): bool;

    public function getProject(string $id): ?ProjectDTO;

    public function getProjectGraph(string $projectId): ?GraphDTO;

    /**
     * @return array<StatusDTO>
     */
    public function getProjectStatus(string $projectId): array;

    /**
     * @return ProjectDTO[]
     */
    public function getProjects(): array;
    
    public function insertProject(ProjectDTO $project): bool;
    public function updateProject(ProjectDTO $project): bool;
    public function deleteProject(string $id): bool;
    public function insertProjectNode(string $projectId, string $nodeId): bool;
    public function deleteProjectNode(string $projectId, string $nodeId): bool;

    /**
     * @return LogDTO[]
     */
    public function getLogs(int $limit): array;

    public function insertLog(LogDTO $log): bool;
}