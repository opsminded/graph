<?php

declare(strict_types=1);

final class Service implements ServiceInterface
{
    private const SECURE_ACTIONS = [
        "Service::getUser"             => true,
        "Service::getCategories"       => true,
        "Service::getTypes"            => true,
        "Service::getNode"             => true,
        "Service::getNodes"            => true,
        "Service::getNodeParentOf"     => true,
        "Service::getDependentNodesOf" => true,
        "Service::getEdge"             => true,
        "Service::getEdges"            => true,
        "Service::getStatus"           => true,
        "Service::getNodeStatus"       => true,
        "Service::getProject"          => true,
        "Service::getProjects"         => true,
        "Service::getLogs"             => true,
        "Service::insertUser"          => false,
        "Service::updateUser"          => false,
        "Service::insertCategory"      => false,
        "Service::insertType"          => false,
        "Service::insertNode"          => false,
        "Service::updateNode"          => false,
        "Service::deleteNode"          => false,
        "Service::insertEdge"          => false,
        "Service::updateEdge"          => false,
        "Service::deleteEdge"          => false,
        "Service::updateNodeStatus"    => false,
        "Service::insertProject"       => false,
        "Service::updateProject"       => false,
        "Service::deleteProject"       => false,
        "Service::insertLog"           => false,
    ];

    private const ADMIN_ACTIONS = [
        "Service::insertUser",
        "Service::updateUser",
        "Service::insertCategory",
        "Service::insertType",
    ];

    private DatabaseInterface $database;
    private Logger $logger;

    public function __construct(DatabaseInterface $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    public function getUser(string $id): ?User
    {
        $this->logger->debug("getting user", [User::USER_KEYNAME_ID => $id]);
        $this->verify();
        $dbUser = $this->database->getUser($id);
        if (! is_null($dbUser)) {
            $g = new Group($dbUser->group);
            $user = new User($id, $g);
            $this->logger->debug("user found", [User::USER_KEYNAME_ID => $id, "user" => $dbUser]);
            return $user;
        }
        $this->logger->debug("user not found", [User::USER_KEYNAME_ID => $id]);
        return null;
    }

    public function insertUser(User $user): bool
    {
        $this->logger->debug("inserting user", ["user" => $user->toArray()]);
        $this->verify();

        $dto = new UserDTO($user->getId(), $user->getGroup()->getId());

        $this->database->insertUser($dto);
        $this->logger->debug("user inserted", ["user" => $user->toArray()]);
        return true;
    }

    public function updateUser(User $user): bool
    {
        $this->logger->debug("updating user", ["user" => $user->toArray()]);
        $this->verify();

        $dto = new UserDTO($user->getId(), $user->getGroup()->getId());

        if ($this->database->updateUser($dto)) {
            $this->logger->debug("user updated", ["user" => $user->toArray()]);
            return true;
        }
        return false;
    }

    public function getCategories(): array
    {
        $this->logger->debug("getting categories");
        $this->verify();
        $dbCategories = $this->database->getCategories();
        $categories     = [];
        foreach ($dbCategories as $ctg) {
            $category = new Category(
                $ctg->id,
                $ctg->name,
                $ctg->shape,
                $ctg->width,
                $ctg->height
            );
            $categories[] = $category;
        }
        return $categories;
    }

    public function insertCategory(Category $category): bool
    {
        $this->logger->debug("inserting category", ["category" => $category->toArray()]);
        $this->verify();
        $dto = new CategoryDTO($category->getId(), $category->getName(), $category->getShape(), $category->getWidth(), $category->getHeight());
        if ($this->database->insertCategory($dto)) {
            $this->logger->info("category inserted", ["category" => $category->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertCategory");
    }
    
    public function getTypes(): array
    {
        $this->logger->debug("getting types");
        $this->verify();
        $dbTypes = $this->database->getTypes();
        $types     = [];
        foreach ($dbTypes as $tp) {
            $type = new Type(
                $tp->id,
                $tp->name
            );
            $types[] = $type;
        }
        return $types;
    }
    public function insertType(Type $type): bool
    {
        $this->logger->debug("inserting type", ["type" => $type->toArray()]);
        $this->verify();
        $dto = new TypeDTO($type->getId(), $type->getName());
        if ($this->database->insertType($dto)) {
            $this->logger->info("type inserted", ["type" => $type->toArray()]);
            return true;
        }
        return false;
    }

    public function getNode(string $id): ?Node
    {
        $this->logger->debug("getting node", ["id" => $id]);
        $this->verify();
        $node = $this->database->getNode($id);
        if (! is_null($node)) {
            return new Node(
                $node->id,
                $node->label,
                $node->category,
                $node->type,
                $node->userCreated,
                $node->data
            );
        }
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug("getting nodes");
        $this->verify();
        $nodesData = $this->database->getNodes();
        $nodes     = [];
        foreach ($nodesData as $node) {
            $new = new Node(
                $node->id,
                $node->label,
                $node->category,
                $node->type,
                $node->userCreated,
                $node->data
            );
            $nodes[] = $new;
        }
        return $nodes;
    }

    public function insertNode(Node $node): bool
    {
        $this->logger->debug("inserting node", $node->toArray());
        $this->verify();
        $this->logger->debug("permission allowed", $node->toArray());
        $this->insertLog("node", $node->getId(), "insert", null, $node->toArray());

        $dto = new NodeDTO($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getUserCreated(), $node->getData());

        if ($this->database->insertNode($dto)) {
            $this->logger->info("node inserted", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertNode");
    }

    public function updateNode(Node $node): bool
    {
        $this->logger->debug("updating node", ["node" => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error("node not found", $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog("node", $node->getId(), "update", $old->toArray(), $node->toArray());
        $dto = new NodeDTO($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getUserCreated(), $node->getData());
        if ($this->database->updateNode($dto)) {
            $this->logger->info("node updated", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNode");
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug("deleting node", ["id" => $id]);
        $this->verify();
        $exists = $this->database->getNode($id);
        if (is_null($exists)) {
            $this->logger->error("node not found", ["id" => $id]);
            return false;
        }
        $old = $this->getNode($id);
        $this->insertLog("node", $id, "delete", $old->toArray(), null);
        if ($this->database->deleteNode($id)) {
            $this->logger->info("node deleted", ['id' => $id]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteNode");
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        $this->logger->debug("getting edge", [Edge::EDGE_KEYNAME_SOURCE => $source, Edge::EDGE_KEYNAME_TARGET => $target]);
        $this->verify();
        $id = "{$source}-{$target}";
        $dbEdge = $this->database->getEdge($id);
        
        if (! is_null($dbEdge)) {
            $edge = new Edge(
                $dbEdge->source,
                $dbEdge->target,
                $dbEdge->label,
                $dbEdge->data
            );
            
            $data = $edge->toArray();
            $this->logger->info("edge found", $data);
            return $edge;
        }
        $this->logger->info("edge not found", [Edge::EDGE_KEYNAME_SOURCE => $source, Edge::EDGE_KEYNAME_TARGET => $target]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug("getting edges");
        $this->verify();

        $dbEdges = $this->database->getEdges();
        $edges     = [];
        foreach ($dbEdges as $edge) {
            $edge = new Edge(
                $edge->source,
                $edge->target,
                $edge->label,
                $edge->data
            );
            $edges[] = $edge;
        }
        return $edges;
    }

    public function insertEdge(Edge $edge): bool
    {
        $this->logger->debug("inserting edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $this->insertLog("edge", $edge->getId(), "insert", null, $edge->toArray());
        $dto = new EdgeDTO($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getLabel(), $edge->getData());
        if ($this->database->insertEdge($dto)) {
            $this->logger->info("edge inserted", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertEdge");
    }

    public function updateEdge(Edge $edge): bool
    {
        $this->logger->debug("updating edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getId());
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["edge" => $edge->toArray()]);
            return false;
        }

        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog("edge", $edge->getId(), "update", $old->toArray(), $edge->toArray());
        $dto = new EdgeDTO($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getLabel(), $edge->getData());
        if ($this->database->updateEdge($dto)) {
            $this->logger->info("edge updated", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateEdge");
    }

    public function deleteEdge(string $source, string $target): bool
    {
        $this->logger->debug("deleting edge", ["source" => $source, "target" => $target]);
        $this->verify();
        $id = "{$source}-{$target}";
        $exists = $this->database->getEdge($id);
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["source" => $source, "target" => $target]);
            return false;
        }
        $old = $this->getEdge($source, $target);
        $this->insertLog("edge", "{$source}-{$target}", "delete", $old->toArray(), null);
        $id = "{$source}-{$target}";
        if ($this->database->deleteEdge($id)) {
            $this->logger->info("edge deleted", ["source" => $source, "target" => $target]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteEdge");
    }

    public function getNodeStatus(string $id): ?Status
    {
        $this->logger->debug("getting node status", ["id" => $id]);
        $this->verify();
        $nodeStatus = $this->database->getNodeStatus($id);

        if (! is_null($nodeStatus)) {
            $this->logger->info("status found", ['id' => $id]);
            return new Status($id, $nodeStatus->status ?? "unknown");
        }
        $this->logger->info("status not found", ["id" => $id]);
        return null;
    }

    public function updateNodeStatus(Status $status): bool
    {
        $this->logger->debug("updating node status", ["status" => $status->toArray()]);
        $this->verify();
        $data = $status->toArray();
        $dto = new StatusDTO($status->getNodeId(), $status->getStatus());
        if ($this->database->updateNodeStatus($dto)) {
            $this->logger->info("node status updated", $data);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNodeStatus");
    }

    public function getProject(string $id): ?Project
    {
        $this->logger->debug("getting project", ["id" => $id]);
        $this->verify();
        $dbProject = $this->database->getProject($id);

        if (! is_null($dbProject)) {
            $project = new Project(
                $dbProject->id,
                $dbProject->name,
                $dbProject->author,
                $dbProject->createdAt,
                $dbProject->updatedAt,
                null
            );
            $this->logger->info("project found", ["project" => $project]);
            return $project;
        }
        $this->logger->info("project not found", ["id" => $id]);
        return null;
    }

    public function getProjects(): array
    {
        $this->logger->debug("getting projects");
        $this->verify();
        $dbProjects = $this->database->getProjects();
        
        $projects     = [];

        foreach ($dbProjects as $project) {
            $project = new Project(
                $project->id,
                $project->name,
                $project->author,
                $project->createdAt,
                $project->updatedAt,
                null
            );
            $projects[] = $project;
        }
        return $projects;
    }

    public function insertProject(Project $project): bool
    {
        $this->logger->debug("inserting project", ["project" => $project->toArray()]);
        $this->verify();

        $dto = new ProjectDTO(
            $project->getId(),
            $project->getName(),
            $project->getAuthor(),
            $project->getCreatedAt(),
            $project->getUpdatedAt(),
            null,
            $project->getData()
        );
        return $this->database->insertProject($dto);
    }

    public function updateProject(Project $project): bool
    {
        $this->logger->debug("updating project", ["project" => $project->toArray()]);
        $this->verify();

        $dto = new ProjectDTO(
            $project->getId(),
            $project->getName(),
            $project->getAuthor(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
            $project->getData()
        );

        return $this->database->updateProject($dto);
    }

    public function deleteProject(string $id): bool
    {
        $this->logger->debug("deleting project", ["id" => $id]);
        $this->verify();
        return $this->database->deleteProject($id);
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug("getting logs", ["limit" => $limit]);
        $this->verify();
        
        $dbLogs = $this->database->getLogs($limit);
        
        $logs = [];
        foreach ($dbLogs as $log) {
            $log = new Log(
                $log->entityType,
                $log->entityId,
                $log->action,
                $log->oldData,
                $log->newData,
                $log->userId,
                $log->ipAddress,
                $log->timestamp
            );

            $logs[] = $log;
        }
        return $logs;
    }

    private function insertLog(
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldData = null,
        ?array $newData = null
    ): void
    {
        $userId   = HelperContext::getUser();
        $ipAddress = HelperContext::getClientIP();

        $dto = new LogDTO(
            $entityType,
            $entityId,
            $action,
            $oldData,
            $newData,
            $userId,
            $ipAddress,
            new DateTimeImmutable()
        );
        
        $this->database->insertLog($dto);
    }

    private function verify(): void
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";
        $group  = HelperContext::getGroup();

        $this->logger->debug("verify", ["action" => $action, "group" => $group]);

        if (! array_key_exists($action, self::SECURE_ACTIONS)) {
            $this->logger->error("action not found in SECURE_ACTIONS", ["action" => $action]);
            throw new RuntimeException("action not found in SECURE_ACTIONS: " . $action);
        }

        if (in_array($action, self::ADMIN_ACTIONS, true) && $group !== "admin") {
            $this->logger->info("only admin allowed", ["action" => $action, "group" => $group]);
            throw new RuntimeException("action only allowed for admin: " . $action);
        }

        // if action is in the SECURE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            $this->logger->info("allow safe action", ["action" => $action, "group" => $group]);
            return;
        }

        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] === false && in_array($group, ["admin", "contributor"], true)) {
            $this->logger->info("contributor and admin are allowed", ["action" => $action, "group" => $group]);
            return;
        }

        $this->logger->info("not authorized", ["action" => $action, "group" => $group]);
        throw new RuntimeException("action not allowed: " . $action);
    }
}