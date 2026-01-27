<?php

declare(strict_types=1);

final class Service implements ServiceInterface
{
    private const SECURE_ACTIONS = [
        "Service::getUser"             => true,
        "Service::getCategories"       => true,
        "Service::getTypes"            => true,
        "Service::getGraph"            => true,
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

    public function getUser(string $id): ?ModelUser
    {
        $this->logger->debug("getting user", [ModelUser::USER_KEYNAME_ID => $id]);
        $this->verify();
        $data = $this->database->getUser($id);
        if (! is_null($data)) {
            $g = new ModelGroup($data[ModelUser::USER_KEYNAME_GROUP]);
            $user = new ModelUser($id, $g);
            $this->logger->debug("user found", [ModelUser::USER_KEYNAME_ID => $id, "user" => $data]);
            return $user;
        }
        $this->logger->debug("user not found", [ModelUser::USER_KEYNAME_ID => $id]);
        return null;
    }

    public function insertUser(ModelUser $user): bool
    {
        $this->logger->debug("inserting user", ["user" => $user->toArray()]);
        $this->verify();
        $this->database->insertUser($user->getId(), $user->getGroup()->getId());
        $this->logger->debug("user inserted", ["user" => $user->toArray()]);
        return true;
    }

    public function updateUser(ModelUser $user): bool
    {
        $this->logger->debug("updating user", ["user" => $user->toArray()]);
        $this->verify();
        if ($this->database->updateUser($user->getId(), $user->getGroup()->getId())) {
            $this->logger->debug("user updated", ["user" => $user->toArray()]);
            return true;
        }
        return false;
    }

    public function getCategories(): array
    {
        $this->logger->debug("getting categories");
        $this->verify();
        $categoriesData = $this->database->getCategories();
        $categories     = [];
        foreach ($categoriesData as $data) {
            $category = new ModelCategory(
                $data[ModelCategory::CATEGORY_KEYNAME_ID],
                $data[ModelCategory::CATEGORY_KEYNAME_NAME],
                $data[ModelCategory::CATEGORY_KEYNAME_SHAPE],
                (int)$data[ModelCategory::CATEGORY_KEYNAME_WIDTH],
                (int)$data[ModelCategory::CATEGORY_KEYNAME_HEIGHT],
            );
            $categories[] = $category;
        }
        return $categories;
    }

    public function insertCategory(ModelCategory $category): bool
    {
        $this->logger->debug("inserting category", ["category" => $category->toArray()]);
        $this->verify();
        if ($this->database->insertCategory($category->id, $category->name, $category->shape, $category->width, $category->height)) {
            $this->logger->info("category inserted", ["category" => $category->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertCategory");
    }
    
    public function getTypes(): array
    {
        $this->logger->debug("getting types");
        $this->verify();
        $typesData = $this->database->getTypes();
        $types     = [];
        foreach ($typesData as $data) {
            $type = new ModelType(
                $data[ModelType::TYPE_KEYNAME_ID],
                $data[ModelType::TYPE_KEYNAME_NAME],
            );
            $types[] = $type;
        }
        return $types;
    }
    public function insertType(ModelType $type): bool
    {
        $this->logger->debug("inserting type", ["type" => $type->toArray()]);
        $this->verify();
        if ($this->database->insertType($type->id, $type->name)) {
            $this->logger->info("type inserted", ["type" => $type->toArray()]);
            return true;
        }
        return false;
    }

    public function getGraph(): ModelGraph
    {
        $this->logger->debug("getting graph");
        $this->verify();
        $nodes = $this->getNodes();
        $edges = $this->getEdges();
        $graph = new ModelGraph($nodes, $edges);
        $this->logger->debug("returning graph", $graph->toArray());
        return $graph;
    }

    public function getNode(string $id): ?ModelNode
    {
        $this->logger->debug("getting node", ["id" => $id]);
        $this->verify();
        $data = $this->database->getNode($id);
        if (! is_null($data)) {
            return new ModelNode(
                $data[ModelNode::NODE_KEYNAME_ID],
                $data[ModelNode::NODE_KEYNAME_LABEL],
                $data[ModelNode::NODE_KEYNAME_CATEGORY],
                $data[ModelNode::NODE_KEYNAME_TYPE],
                $data[ModelNode::NODE_KEYNAME_USERCREATED],
                $data[ModelNode::NODE_KEYNAME_DATA]
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
        foreach ($nodesData as $data) {
            
            $node = new ModelNode(
                $data[ModelNode::NODE_KEYNAME_ID],
                $data[ModelNode::NODE_KEYNAME_LABEL],
                $data[ModelNode::NODE_KEYNAME_CATEGORY],
                $data[ModelNode::NODE_KEYNAME_TYPE],
                $data[ModelNode::NODE_KEYNAME_USERCREATED],
                $data[ModelNode::NODE_KEYNAME_DATA]
            );
            $nodes[] = $node;
        }
        return $nodes;
    }

    public function insertNode(ModelNode $node): bool
    {
        $this->logger->debug("inserting node", $node->toArray());
        $this->verify();
        $this->logger->debug("permission allowed", $node->toArray());
        $this->insertLog(new ModelLog("node", $node->getId(), "insert", null, $node->toArray()));
        if ($this->database->insertNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getUserCreated(), $node->getData())) {
            $this->logger->info("node inserted", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertNode");
    }

    public function updateNode(ModelNode $node): bool
    {
        $this->logger->debug("updating node", ["node" => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error("node not found", $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog(new ModelLog("node", $node->getId(), "update", $old->toArray(), $node->toArray()));
        if ($this->database->updateNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData())) {
            $this->logger->info("node updated", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNode");
    }

    public function deleteNode(ModelNode $node): bool
    {
        $this->logger->debug("deleting node", ["node" => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error("node not found", $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog(new ModelLog( "node", $node->getId(), "delete", $old->toArray(), null));
        if ($this->database->deleteNode($node->getId())) {
            $this->logger->info("node deleted", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteNode");
    }

    public function getEdge(string $source, string $target): ?ModelEdge
    {
        $this->logger->debug("getting edge", [ModelEdge::EDGE_KEYNAME_SOURCE => $source, ModelEdge::EDGE_KEYNAME_TARGET => $target]);
        $this->verify();
        $edgeData = $this->database->getEdge($source, $target);
        if (! is_null($edgeData)) {
            $edge = new ModelEdge(
                $edgeData[ModelEdge::EDGE_KEYNAME_SOURCE],
                $edgeData[ModelEdge::EDGE_KEYNAME_TARGET],
                $edgeData[ModelEdge::EDGE_KEYNAME_LABEL],
                $edgeData[ModelEdge::EDGE_KEYNAME_DATA]
            );
            
            $data = $edge->toArray();
            $this->logger->info("edge found", $data);
            return $edge;
        }
        $this->logger->info("edge not found", [ModelEdge::EDGE_KEYNAME_SOURCE => $source, ModelEdge::EDGE_KEYNAME_TARGET => $target]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug("getting edges");
        $this->verify();

        $edgesData = $this->database->getEdges();
        $edges     = [];
        foreach ($edgesData as $data) {
            $edge = new ModelEdge(
                $data[ModelEdge::EDGE_KEYNAME_SOURCE],
                $data[ModelEdge::EDGE_KEYNAME_TARGET],
                $data[ModelEdge::EDGE_KEYNAME_LABEL],
                $data[ModelEdge::EDGE_KEYNAME_DATA]
            );
            $edges[] = $edge;
        }
        return $edges;
    }

    public function insertEdge(ModelEdge $edge): bool
    {
        $this->logger->debug("inserting edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $this->insertLog(new ModelLog( "edge", $edge->getId(), "insert", null, $edge->toArray()));
        if ($this->database->insertEdge($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getLabel(), $edge->getData())) {
            $this->logger->info("edge inserted", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertEdge");
    }

    public function updateEdge(ModelEdge $edge): bool
    {
        $this->logger->debug("updating edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getSource(), $edge->getTarget());
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["edge" => $edge->toArray()]);
            return false;
        }

        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog(new ModelLog("edge", $edge->getId(), "update", $old->toArray(), $edge->toArray()));
        if ($this->database->updateEdge($edge->getId(), $edge->getLabel(), $edge->getData())) {
            $this->logger->info("edge updated", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateEdge");
    }

    public function deleteEdge(ModelEdge $edge): bool
    {
        $this->logger->debug("deleting edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getSource(), $edge->getTarget());
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["edge" => $edge->toArray()]);
            return false;
        }
        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog(new ModelLog("edge", $edge->getId(), "delete", $old->toArray(), null));
        if ($this->database->deleteEdge($edge->getId())) {
            $this->logger->info("edge deleted", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteEdge");
    }

    public function getStatus(): array
    {
        $this->logger->debug("getting status");
        $this->verify();
        $statusData = $this->database->getStatus();
        $nodeStatus = [];
        foreach ($statusData as $status) {
            $status = new ModelStatus($status[ModelStatus::STATUS_KEYNAME_NODE_ID], $status[ModelStatus::STATUS_KEYNAME_STATUS] ?? "unknown");
            $nodeStatus[] = $status;
        }
        $this->logger->info("status found", ["status" => $nodeStatus]);
        return $nodeStatus;
    }

    public function getNodeStatus(string $id): ?ModelStatus
    {
        $this->logger->debug("getting node status", ["id" => $id]);
        $this->verify();
        $statusData = $this->database->getNodeStatus($id);
        if (! is_null($statusData)) {
            $this->logger->info("status found", $statusData);
            return new ModelStatus($id, $statusData[ModelStatus::STATUS_KEYNAME_STATUS] ?? "unknown");
        }
        $this->logger->info("status not found", ["id" => $id]);
        return null;
    }

    public function updateNodeStatus(ModelStatus $status): bool
    {
        $this->logger->debug("updating node status", ["status" => $status->toArray()]);
        $this->verify();
        $data = $status->toArray();
        if ($this->database->updateNodeStatus($status->getNodeId(), $status->getStatus())) {
            $this->logger->info("node status updated", $data);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNodeStatus");
    }

    public function getProject(string $id): ?ModelProject
    {
        $this->logger->debug("getting project", ["id" => $id]);
        $this->verify();
        $data = $this->database->getProject($id);

        if (! is_null($data)) {
            $nodes = [];
            // echo "data1\n";
            // print_r($data);
            // exit();
            foreach($data['nodes'] as $n) {
                $nodes[] = $n;
            }
            $project = new ModelProject(
                $data[ModelProject::PROJECT_KEYNAME_ID],
                $data[ModelProject::PROJECT_KEYNAME_NAME],
                $data[ModelProject::PROJECT_KEYNAME_AUTHOR],
                new DateTimeImmutable($data[ModelProject::PROJECT_KEYNAME_CREATED_AT]),
                new DateTimeImmutable($data[ModelProject::PROJECT_KEYNAME_UPDATED_AT]),
                $nodes
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
        $projectsData = $this->database->getProjects();
        $projects     = [];

        foreach ($projectsData as $data) {
            echo "data2\n";
            print_r($data);
            exit();
            $nodes = [];
            foreach($data['nodes'] as $n) {
                $nodes[] = $n;
            }

            $project = new ModelProject(
                $data[ModelProject::PROJECT_KEYNAME_ID],
                $data[ModelProject::PROJECT_KEYNAME_NAME],
                $data[ModelProject::PROJECT_KEYNAME_AUTHOR],
                new DateTimeImmutable($data[ModelProject::PROJECT_KEYNAME_CREATED_AT]),
                new DateTimeImmutable($data[ModelProject::PROJECT_KEYNAME_UPDATED_AT]),
                $nodes
            );
            $projects[] = $project;
        }
        return $projects;
    }

    public function insertProject(ModelProject $project): bool
    {
        $this->logger->debug("inserting project", ["project" => $project->toArray()]);
        $this->verify();

        $data = [
            'nodes' => []
        ];

        foreach($project->nodes as $node) {
            $data['nodes'][] = $node;
        }

        return $this->database->insertProject($project->id, $project->name, $project->author, $data);
    }

    public function updateProject(ModelProject $project): bool
    {
        $this->logger->debug("updating project", ["project" => $project->toArray()]);
        $this->verify();

        $data = [
            'nodes' => []
        ];

        foreach($project->nodes as $node) {
            $data['nodes'][] = $node;
        }

        return $this->database->updateProject($project->id, $project->name, $project->author, $data);
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
        $logs = [];
        $rows = $this->database->getLogs($limit);
        foreach ($rows as $row) {
            $oldData = $row["old_data"] ? json_decode($row["old_data"], true) : [];
            $newData = $row["new_data"] ? json_decode($row["new_data"], true) : [];
            $log = new ModelLog(
                $row["entity_type"],
                $row["entity_id"],
                $row["action"],
                $oldData,
                $newData,
            );
            $log->userId    = $row["user_id"];
            $log->ipAddress = $row["ip_address"];
            $log->createdAt = $row["created_at"];
            $logs[] = $log;
        }
        return $logs;
    }

    private function insertLog(ModelLog $log): void
    {
        $this->logger->debug("insert log", ["log" => $log]);
        $userId   = HelperContext::getUser();
        $ipAddress = HelperContext::getClientIP();
        $this->database->insertLog($log->entityType, $log->entityId, $log->action, $log->oldData, $log->newData, $userId, $ipAddress);
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