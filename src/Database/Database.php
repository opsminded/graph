<?php

declare(strict_types=1);

final class Database implements DatabaseInterface
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->initSchema();
    }

    public function getUser(string $id): ?UserDTO
    {
        $this->logger->debug("getting user id", ['id' => $id]);
        $sql = "SELECT id, user_group as \"group\" FROM users WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("user found", ['params' => $params, 'row' => $row]);
            return new UserDTO($row['id'], $row['group']);
        }
        $this->logger->info("user not found", ['params' => $params]);
        return null;
    }

    public function getUsers(): array
    {
        $this->logger->debug("fetching users");
        $sql = "SELECT id, user_group as \"group\" FROM users";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        $this->logger->info("users fetched", ['rows' => $rows]);
        return array_map(fn($row) => new UserDTO($row['id'], $row['group']), $rows);
    }

    public function insertUser(UserDTO $user): bool
    {
        $this->logger->debug("inserting new user", ['id' => $user->id, 'group' => $user->group]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $params = [':id' => $user->id, ':group' => $user->group];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === '23000') {
                $this->logger->error("user already exists", ['params' => $params]);
                $complementary = " - user already exists";
            }

            throw new DatabaseException("Failed to insert user" . $complementary, $exception);
        }
    }

    public function batchInsertUsers(array $users): bool
    {
        $this->logger->debug("batch inserting users", ['users' => $users]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($users as $user) {
            $params = [':id' => $user['id'], ':group' => $user['group']];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === '23000') {
                    $complementary = "user already exists: ";
                    $complementary .= $user['id'];
                    $this->logger->error("user already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to insert user in batch: " . $complementary, $exception);
            }
        }
        $this->logger->info("batch users inserted", ['users' => $users]);
        return true;
    }

    public function updateUser(UserDTO $user): bool
    {
        $this->logger->debug("updating new user", ['id' => $user->id, 'group' => $user->group]);
        $sql = "UPDATE users SET user_group = :group WHERE id = :id";
        $params = [':id' => $user->id, ':group' => $user->group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->info("user updated", ['params' => $params]);
            return true;
        }
        $this->logger->info("user not updated", ['params' => $params]);
        return false;
    }

    public function deleteUser(string $id): bool
    {
        $this->logger->debug("deleting user", ['id' => $id]);
        $sql = "DELETE FROM users WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->info("user deleted", ['params' => $params]);
            return true;
        }
        $this->logger->info("user not deleted", ['params' => $params]);
        return false;
    }

    public function getCategory(string $id): ?CategoryDTO
    {
        $this->logger->debug("fetching category", ['id' => $id]);
        $sql = "SELECT * FROM categories WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("category fetched", ['params' => $params, 'row' => $row]);
            return new CategoryDTO($row['id'], $row['name'], $row['shape'], (int)$row['width'], (int)$row['height']);
        }
        $this->logger->info("category not found", ['params' => $params]);
        return null;
    }

    public function getCategories(): array
    {
        $this->logger->debug("fetching categories");
        $sql = "SELECT * FROM categories";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        $this->logger->info("categories fetched", ['rows' => $rows]);
        return array_map(fn($row) => new CategoryDTO($row['id'], $row['name'], $row['shape'], (int)$row['width'], (int)$row['height']), $rows);
    }

    public function insertCategory(CategoryDTO $category): bool
    {
        $this->logger->debug("inserting new category", ['id' => $category->id, 'name' => $category->name]);
        $sql = "INSERT INTO categories (id, name, shape, width, height) VALUES (:id, :name, :shape, :width, :height)";
        $params = [':id' => $category->id, ':name' => $category->name, ':shape' => $category->shape, ':width' => $category->width, ':height' => $category->height];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("category inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === '23000') {
                $this->logger->error("category already exists", ['params' => $params]);
                $complementary = " - category already exists: " . $category->id;
            }
            throw new DatabaseException("Failed to insert category" . $complementary, $exception);
        }
    }

    public function updateCategory(CategoryDTO $category): bool
    {
        $this->logger->debug("updating category", ['id' => $category->id, 'name' => $category->name, 'shape' => $category->shape, 'width' => $category->width, 'height' => $category->height]);
        $sql = "UPDATE categories SET name = :name, shape = :shape, width = :width, height = :height WHERE id = :id";
        $params = [':id' => $category->id, ':name' => $category->name, ':shape' => $category->shape, ':width' => $category->width, ':height' => $category->height];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("category updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("category not updated", ['params' => $params]);
        return false;
    }

    public function deleteCategory(string $id): bool
    {
        $this->logger->debug('deleting category', ['id' => $id]);
        $sql = "DELETE FROM categories WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("category deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("category not deleted", ['params' => $params]);
        return false;
    }

    public function getType(string $id): ?TypeDTO
    {
        $this->logger->debug("fetching type", ['id' => $id]);
        $sql = "SELECT * FROM types WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("type fetched", ['params' => $params, 'row' => $row]);
            return new TypeDTO($row['id'], $row['name']);
        }
        $this->logger->info("type not found", ['params' => $params]);
        return null;
    }
    
    public function getTypes(): array
    {
        $this->logger->debug("fetching types");
        $sql = "SELECT * FROM types";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        $this->logger->info("types fetched", ['rows' => $rows]);
        return array_map(fn($row) => new TypeDTO($row['id'], $row['name']), $rows);
    }

    public function insertType(TypeDTO $type): bool
    {
        $this->logger->debug("inserting new type", ['id' => $type->id, 'name' => $type->name]);
        $sql = "INSERT INTO types (id, name) VALUES (:id, :name)";
        $params = [':id' => $type->id, ':name' => $type->name];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("type inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === '23000') {
                $this->logger->error("Type already exists", ['params' => $params]);
                $complementary = "Type already exists: " . $type->id;
            }
            throw new DatabaseException("Failed to insert type. " . $complementary, $exception);
        }
    }

    public function updateType(TypeDTO $type): bool
    {
        $this->logger->debug("updating type", ['id' => $type->id, 'name' => $type->name]);
        $sql = "UPDATE types SET name = :name WHERE id = :id";
        $params = [':id' => $type->id, ':name' => $type->name];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("type updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("type not updated", ['params' => $params]);
        return false;
    }

    public function deleteType(string $id): bool
    {
        $this->logger->debug("deleting type", ['id' => $id]);
        $sql = "DELETE FROM types WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("type deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("type not deleted", ['params' => $params]);
        return false;
    }

    public function getNode(string $id): ?NodeDTO
    {
        $this->logger->debug("fetching node", ['id' => $id]);
        $sql = "SELECT * FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row[Node::NODE_KEYNAME_USERCREATED] = (bool)$row[Node::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("node fetched", ['params' => $params, 'row' => $row]);
            return new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row[Node::NODE_KEYNAME_USERCREATED], $row['data']);
        }
        $this->logger->info("node not found", ['params' => $params]);
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug("fetching nodes");
        $sql = "SELECT * FROM nodes";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row[Node::NODE_KEYNAME_USERCREATED] = (bool)$row[Node::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("nodes fetched", ['rows' => $rows]);
        return array_map(fn($row) => new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row[Node::NODE_KEYNAME_USERCREATED], $row['data']), $rows);
    }

    public function insertNode(NodeDTO $node): bool
    {
        $this->logger->debug("inserting new node", ['id' => $node->id, 'label' => $node->label, 'category' => $node->category, 'type' => $node->type, 'userCreated' => $node->userCreated, 'data' => $node->data]);
        $sql = "INSERT INTO nodes (id, label, category, type, user_created, data) VALUES (:id, :label, :category, :type, :user_created, :data)";
        $data = json_encode($node->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $node->id, ':label' => $node->label, ':category' => $node->category, ':type' => $node->type, ':user_created' => $node->userCreated, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("node inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === '23000') {
                $this->logger->error("Node already exists", ['params' => $params]);
                $complementary = "Node already exists: " . $node->id;
            }
            throw new DatabaseException("Failed to insert node. " . $complementary, $exception);
        }
    }

    public function batchInsertNodes(array $nodes): bool
    {
        $this->logger->debug("batch inserting nodes", ['nodes' => $nodes]);
        $sql = "INSERT INTO nodes (id, label, category, type, user_created, data) VALUES (:id, :label, :category, :type, :user_created, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($nodes as $node) {
            $data = json_encode($node->data ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $node->id,
                ':label' => $node->label,
                ':category' => $node->category,
                ':type' => $node->type,
                ':user_created' => $node->userCreated ?? false,
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === '23000') {
                    $complementary = "Node already exists: ";
                    $complementary .= $node->id;
                    $this->logger->error("node already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to batch insert nodes. " . $complementary, $exception);
            }
        }
        $this->logger->info("batch nodes inserted", ['nodes' => $nodes]);
        return true;
    }

    public function updateNode(NodeDTO $node): bool
    {
        $this->logger->debug("updating node", ['id' => $node->id, 'label' => $node->label, 'category' => $node->category, 'type' => $node->type, 'data' => $node->data]);
        $sql = "UPDATE nodes SET label = :label, category = :category, type = :type, data = :data WHERE id = :id";
        $data = json_encode($node->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $node->id, ':label' => $node->label, ':category' => $node->category, ':type' => $node->type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("node updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("node not updated", ['params' => $params]);
        return false;
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug("deleting node", ['id' => $id]);
        $sql = "DELETE FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->debug("node deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("node not deleted", ['params' => $params]);
        return false;
    }

    public function getEdge(string $id): ?EdgeDTO
    {
        $this->logger->debug("getting edge", ['id' => $id]);
        $sql = "SELECT * FROM edges WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("edge found", ['params' => $params, 'row' => $row]);
            return new EdgeDTO($row['id'], $row['source'], $row['target'], $row['label'], $row['data']);
        }
        $this->logger->info("edge not found", ['params' => $params]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug("fetching edges");
        $sql = "SELECT * FROM edges";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("edges fetched", ['rows' => $rows]);
        return $rows;
    }

    public function insertEdge(EdgeDTO $edge): bool
    {
        $this->logger->debug("inserting edge", ['id' => $edge->id, 'source' => $edge->source, 'target' => $edge->target, 'label' => $edge->label, 'data' => $edge->data]);
        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $data = json_encode($edge->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $edge->id, ':source' => $edge->source, ':target' => $edge->target, ':label' => $edge->label, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($params);
            $this->logger->info("edge inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === '23000') {
                $this->logger->error("Edge already exists", ['params' => $params]);
                $complementary = "Edge already exists: " . $edge->id;
            }
            throw new DatabaseException("Failed to insert edge. " . $complementary, $exception);
        }
    }

    public function batchInsertEdges(array $edges): bool
    {
        $this->logger->debug("batch inserting edges", ['edges' => $edges]);
        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($edges as $edge) {
            $data = json_encode($edge['data'] ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $edge['id'],
                ':source' => $edge['source'],
                ':target' => $edge['target'],
                ':label' => $edge['label'],
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === '23000') {
                    $complementary = "Edge already exists: ";
                    $complementary .= $edge['id'];
                    $this->logger->error("edge already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to batch insert edges. " . $complementary, $exception);
            }
        }
        $this->logger->info("batch edges inserted", ['edges' => $edges]);
        return true;
    }

    public function updateEdge(EdgeDTO $edge): bool
    {
        $this->logger->debug("updating edge", ['id' => $edge->id, 'label' => $edge->label, 'data' => $edge->data]);
        $sql = "UPDATE edges SET label = :label, data = :data WHERE id = :id";
        $data = json_encode($edge->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $edge->id, ':label' => $edge->label, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->info("edge updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("edge not updated", ['params' => $params]);
        return false;
    }

    public function deleteEdge(string $id): bool
    {
        $this->logger->debug("deleting edge", ['id' => $id]);
        $sql = "DELETE FROM edges WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("edge deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("edge not deleted", ['params' => $params]);
        return false;
    }

    public function getStatus(): array
    {
        $this->logger->debug("fetching status");
        $sql = "SELECT n.id as node_id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id";
        $stmt   = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info("status fetched", ['rows' => $rows]);
        return $rows;
    }

    public function getNodeStatus(string $id): ?NodeStatusDTO
    {
        $this->logger->debug("fetching node status", ['id' => $id]);
        $sql = "SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id WHERE n.id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if($row) {
            $this->logger->info("node status fetched", ['params' => $params, 'row' => $row]);
            return new NodeStatusDTO($row['id'], $row['status']);
        }
        return null;
    }

    public function updateNodeStatus(NodeStatusDTO $nodeStatus): bool
    {
        $this->logger->debug("updating node status", ['id' => $nodeStatus->node_id, 'status' => $nodeStatus->status]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $params = [':node_id' => $nodeStatus->node_id, ':status' => $nodeStatus->status];
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($params);
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->errorInfo[1] == 19) {
                $complementary .= "node not found for status update: " . $nodeStatus->node_id;
                $this->logger->error("Node not found for status update", ['params' => $params]);
            }
            throw new DatabaseException("Failed to update node status: " . $complementary, $exception);
        }
        $this->logger->info("node status updated", ['params' => $params]);
        return true;
    }

    public function batchUpdateNodeStatus(array $statuses): bool
    {
        $this->logger->debug("batch updating node statuses", ['statuses' => $statuses]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($statuses as $status) {
            $params = [':node_id' => $status->node_id, ':status' => $status->status];
            $stmt->execute($params);
        }
        $this->logger->info("batch node statuses updated", ['statuses' => $statuses]);
        return true;
    }

    public function getProject(string $id): ?ProjectDTO
    {
        $this->logger->debug("fetching project", ['id' => $id]);
        $sql = "SELECT * FROM projects WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {

            $project = new ProjectDTO(
                $row['id'],
                $row['name'],
                $row['author'],
                json_decode($row['data'], true),
                $row['created_at'],
                $row['updated_at'],
            );

            return $project;
        }
        $this->logger->info("project not found", ['params' => $params]);
        return null;
    }

    public function getProjects(): array
    {
        $this->logger->debug("fetching projects");
        $sql = "SELECT * FROM projects";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("projects fetched", ['rows' => $rows]);
        return $rows;
    }

    public function insertProject(ProjectDTO $project): bool
    {
        $this->logger->debug("inserting new project", ['id' => $project->id, 'name' => $project->name, 'author' => $project->author, 'data' => $project->data]);
        $sql = "INSERT INTO projects (id, name, author, data) VALUES (:id, :name, :author, :data)";
        $data = json_encode($project->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $project->id, ':name' => $project->name, ':author' => $project->author, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($params);
            $this->logger->info("project inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            throw new DatabaseException("Failed to insert project", $exception);
        }
    }

    public function updateProject(ProjectDTO $project): bool
    {
        $this->logger->debug("updating project", ['id' => $project->id, 'name' => $project->name, 'author' => $project->author, 'data' => $project->data]);
        $sql = "UPDATE projects SET name = :name, author = :author, data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $data = json_encode($project->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $project->id, ':name' => $project->name, ':author' => $project->author, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("project not updated", ['params' => $params]);
        return false;
    }

    public function deleteProject(string $id): bool
    {
        $this->logger->debug("deleting project", ['id' => $id]);
        $sql = "DELETE FROM projects WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("project not deleted", ['params' => $params]);
        return false;
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug("fetching logs", ['limit' => $limit]);
        $sql = "SELECT * FROM audit ORDER BY created_at DESC LIMIT :limit";
        $params = [':limit' => $limit];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("logs fetched", ['params' => $params, 'rows' => $rows]);
        return $rows;
    }

    public function insertLog(LogDTO $log): bool
    {
        $this->logger->debug("inserting audit log", ['entity_type' => $log->entityType, 'entity_id' => $log->entityId, 'action' => $log->action, 'old_data' => $log->oldData, 'new_data' => $log->newData, 'user_id' => $log->userId, 'ip_address' => $log->ipAddress]);
        $sql = "INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address) VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)";
        $old_data = $log->oldData !== null ? json_encode($log->oldData, JSON_UNESCAPED_UNICODE) : null;
        $new_data = $log->newData !== null ? json_encode($log->newData, JSON_UNESCAPED_UNICODE) : null;
        $params = [':entity_type' => $log->entityType, ':entity_id' => $log->entityId, ':action' => $log->action, ':old_data' => $old_data, ':new_data' => $new_data, ':user_id' => $log->userId, ':ip_address' => $log->ipAddress];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("audit log inserted", ['params' => $params]);
        return true;
    }

    private function initSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                user_group TEXT NOT NULL
            );
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                shape TEXT NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL
            );
        ');
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS types (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL
            );
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS nodes (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL,
                category TEXT NOT NULL,
                type TEXT NOT NULL,
                user_created BOOLEAN NOT NULL DEFAULT 0,
                data TEXT NOT NULL,
                FOREIGN KEY (category) REFERENCES categories(id),
                FOREIGN KEY (type) REFERENCES types(id)
            );
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS edges (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL DEFAULT "not defined",
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                data TEXT,
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            );
        ');

        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target)');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS status (
                node_id    TEXT PRIMARY KEY NOT NULL,
                status     TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            );
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS projects (
                id         TEXT PRIMARY KEY NOT NULL,
                name       TEXT NOT NULL,
                author     TEXT NOT NULL,
                data       TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ');
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS nodes_projects (
                node_id    TEXT NOT NULL,
                project_id TEXT NOT NULL,
                PRIMARY KEY (node_id, project_id),
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            );
        ');
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS audit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL,
                action TEXT NOT NULL,
                old_data TEXT,
                new_data TEXT,
                user_id TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ');

        $this->pdo->exec('INSERT OR IGNORE INTO users VALUES(\'admin\', \'admin\')');
        
        $this->pdo->exec("INSERT OR IGNORE INTO categories VALUES
            ('business',       'Negócios',       'round-rectangle', 80, 80),
            ('application',    'Aplicação',      'ellipse',         60, 60),
            ('infrastructure', 'Infraestrutura', 'round-hexagon',   60, 53)");

        $this->pdo->exec("INSERT OR IGNORE INTO types VALUES
            ('business',      'Negócios'),
            ('business_case', 'Caso de Uso'),
            ('service',       'Serviço'),
            ('server',        'Servidor'),
            ('database',      'Banco de Dados')");
    }

    public static function createConnection(string $dsn): PDO
    {
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }
}