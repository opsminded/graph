<?php

declare(strict_types=1);

interface ServiceInterface
{
    public function getUser(string $id): ?User;
    public function insertUser(User $user): bool;
    public function updateUser(User $user): bool;

    /**
     * @return array<Category>
     */
    public function getCategories(): array;
    public function insertCategory(Category $category): bool;
    
    /*
     * @return array<Type>
     */
    public function getTypes(): array;
    public function insertType(Type $type): bool;

    /**
     * @return array<Type>
     */
    public function getCategoryTypes(string $category): array;

    public function getNode(string $id): ?Node;
    public function getNodes(): array;
    public function insertNode(Node $node): bool;
    public function updateNode(Node $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): array;
    public function insertEdge(Edge $edge): bool;
    public function updateEdge(Edge $edge): bool;
    public function deleteEdge(string $source, string $target): bool;

    public function updateNodeStatus(Status $status): bool;

    public function getProject(string $id): ?Project;
    public function getProjectGraph(string $id): ?Graph;

    /**
     * @return array<Status>
     */
    public function getProjectStatus(string $id): array;

    public function getProjects(): array;
    public function insertProject(Project $project): bool;
    public function updateProject(Project $project): bool;
    public function deleteProject(string $id): bool;

    /**
     * @return array<Log>
     */
    public function getLogs(int $limit): array;
}