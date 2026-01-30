<?php

declare(strict_types=1);

final class Database implements DatabaseInterface
{
    private const SQLSTATE_CONSTRAINT_VIOLATION = '23000';

    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
        private string $sqlSchema
    ) {
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

    /**                                                                                                                                                                                    
     * @return UserDTO[]                                                                                                                                                                   
     */ 
    public function getUsers(): array
    {
        $this->logger->debug("fetching users");
        $sql = "SELECT id, user_group as \"group\" FROM users";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
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
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("user already exists", ['params' => $params]);
                $complementary = " - user already exists";
            }
            throw new DatabaseException("Failed to insert user" . $complementary, $exception);
        }
    }

    /**
     * @param UserDTO[] $users
     */
    public function batchInsertUsers(array $users): bool
    {
        $this->logger->debug("batch inserting users", ['users' => $users]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($users as $user) {
            $params = [':id' => $user->id, ':group' => $user->group];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                    $complementary = "user already exists: ";
                    $complementary .= $user->id;
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
        $this->logger->debug("updating user", ['id' => $user->id, 'group' => $user->group]);
        $sql = "UPDATE users SET user_group = :group WHERE id = :id";
        $params = [':id' => $user->id, ':group' => $user->group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
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
        if ($stmt->rowCount() > 0) {
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

    /**
     * @return CategoryDTO[]
     */
    public function getCategories(): array
    {
        $this->logger->debug("fetching categories");
        $sql = "SELECT * FROM categories";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
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
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("category already exists", ['params' => $params]);
                $complementary = "category already exists: " . $category->id;
            }
            throw new DatabaseException("Failed to insert category. " . $complementary, $exception);
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
    
    /**
     * @return TypeDTO[]
     */
    public function getTypes(): array
    {
        $this->logger->debug("fetching types");
        $sql = "SELECT * FROM types";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info("types fetched", ['rows' => $rows]);
        return array_map(fn($row) => new TypeDTO($row['id'], $row['name']), $rows);
    }

    /**
     * @return TypeDTO[]
     */
    public function getCategoryTypes(string $categoryId): array
    {
        $this->logger->debug("fetching types for category", ['category' => $categoryId]);
        $sql = "
            SELECT DISTINCT
                       t.id,
                       t.name
            FROM       types t
            INNER JOIN nodes n
            ON         n.type = t.id
            WHERE      n.category = :category_id
        ";
        $params = [':category_id' => $categoryId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $this->logger->info("category types fetched", ['params' => $params, 'rows' => $rows]);
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
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
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
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("node fetched", ['params' => $params, 'row' => $row]);
            return new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row['data']);
        }
        $this->logger->info("node not found", ['params' => $params]);
        return null;
    }

    /**
     * @return NodeDTO[]
     */
    public function getNodes(): array
    {
        $this->logger->debug("fetching nodes");
        $sql = "SELECT * FROM nodes";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("nodes fetched", ['rows' => $rows]);
        return array_map(fn($row) => new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row['data']), $rows);
    }

    public function insertNode(NodeDTO $node): bool
    {
        $this->logger->debug("inserting new node", ['id' => $node->id, 'label' => $node->label, 'category' => $node->category, 'type' => $node->type, 'data' => $node->data]);
        $sql = "INSERT INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)";
        $data = json_encode($node->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $node->id, ':label' => $node->label, ':category' => $node->category, ':type' => $node->type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("node inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Node already exists", ['params' => $params]);
                $complementary = "Node already exists: " . $node->id;
            }
            throw new DatabaseException("Failed to insert node. " . $complementary, $exception);
        }
    }

    /**
     * @param NodeDTO[] $nodes
     */
    public function batchInsertNodes(array $nodes): bool
    {
        $this->logger->debug("batch inserting nodes", ['nodes' => $nodes]);
        $sql = "INSERT INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($nodes as $node) {
            $data = json_encode($node->data ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $node->id,
                ':label' => $node->label,
                ':category' => $node->category,
                ':type' => $node->type,
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
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
        if ($stmt->rowCount() > 0) {
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

    /**
     * @return EdgeDTO[]
     */
    public function getEdges(): array
    {
        $this->logger->debug("fetching edges");
        $sql = "SELECT * FROM edges";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $edges = [];
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
            $edges[] = new EdgeDTO($row['id'], $row['source'], $row['target'], $row['label'], $row['data']);
        }
        $this->logger->info("edges fetched", ['rows' => $rows]);
        return $edges;
    }

    public function insertEdge(EdgeDTO $edge): bool
    {
        $this->logger->debug("inserting edge", ['id' => $edge->id, 'source' => $edge->source, 'target' => $edge->target, 'label' => $edge->label, 'data' => $edge->data]);

        if ($this->wouldCreateCycle($edge->source, $edge->target)) {
            $this->logger->error("Circular reference detected", ['source' => $edge->source, 'target' => $edge->target]);
            throw new DatabaseException("Cannot insert edge: would create circular reference", new Exception());
        }

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
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Edge already exists", ['params' => $params]);
                $complementary = "Edge already exists: " . $edge->id;
            }
            throw new DatabaseException("Failed to insert edge. " . $complementary, $exception);
        }
    }

    /**
     * @param EdgeDTO[] $edges
     */
    public function batchInsertEdges(array $edges): bool
    {
        $this->logger->debug("batch inserting edges", ['edges' => $edges]);

        foreach ($edges as $edge) {
            if ($this->wouldCreateCycle($edge->source, $edge->target)) {
                $this->logger->error("Circular reference detected in batch", ['source' => $edge->source, 'target' => $edge->target]);
                throw new DatabaseException("Cannot insert edge: would create circular reference", new Exception());
            }
        }

        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($edges as $edge) {
            $data = json_encode($edge->data ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $edge->id,
                ':source' => $edge->source,
                ':target' => $edge->target,
                ':label' => $edge->label,
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                    $complementary = "Edge already exists: ";
                    $complementary .= $edge->id;
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
        if ($stmt->rowCount() > 0) {
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

    public function getNodeStatus(string $id): ?StatusDTO
    {
        $this->logger->debug("fetching node status", ['id' => $id]);
        $sql = "SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id WHERE n.id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("node status fetched", ['params' => $params, 'row' => $row]);
            return new StatusDTO($row['id'], $row['status']);
        }
        return null;
    }

    public function updateNodeStatus(StatusDTO $status): bool
    {
        $this->logger->debug("updating node status", ['id' => $status->node_id, 'status' => $status->status]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $params = [':node_id' => $status->node_id, ':status' => $status->status];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $complementary .= "node not found for status update: " . $status->node_id;
                $this->logger->error("Node not found for status update", ['params' => $params]);
            }
            throw new DatabaseException("Failed to update node status: " . $complementary, $exception);
        }
        $this->logger->info("node status updated", ['params' => $params]);
        return true;
    }

    /**
     * @param StatusDTO[] $statuses
     */
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
            $graph = $this->getProjectGraph($id);
            $project = new ProjectDTO(
                $row['id'],
                $row['name'],
                $row['author'],
                new DateTimeImmutable($row['created_at']),
                new DateTimeImmutable($row['updated_at']),
                json_decode($row['data'], true)
            );

            return $project;
        }
        $this->logger->info("project not found", ['params' => $params]);
        return null;
    }

    public function getProjectGraph(string $projectId): ?GraphDTO
    {
        $this->logger->debug("fetching project graph", ['project_id' => $projectId]);
        $sql = '
        WITH RECURSIVE descendants AS (
            SELECT      p.id     as project_id,
                        e.id     as edge_id,
                        e.label  as edge_label,
                        e.source as edge_source_id,
                        e.target as edge_target_id,
                        e.data   as edge_data,
                        0        as edge_depth
            FROM        edges e
            INNER JOIN  nodes_projects np
            ON          e.source = np.node_id
            INNER JOIN  projects p
            ON          np.project_id = p.id
            WHERE       p.id = :project_id
            UNION ALL
            SELECT      d.project_id     as project_id,
                        e.id             as edge_id,
                        e.label          as edge_label,
                        e.source         as edge_source_id,
                        e.target         as edge_target_id,
                        e.data           as edge_data,
                        d.edge_depth + 1 as edge_depth
            FROM        descendants d
            INNER JOIN  edges e ON d.edge_target_id = e.source
        )
        SELECT DISTINCT d.project_id,
                        d.edge_id,
                        d.edge_label,
                        d.edge_data,
                        d.edge_source_id,
                        s.label           as source_label,
                        s.category        as source_category,
                        s.type            as source_type,
                        s.data            as source_data,
                        d.edge_target_id,
                        t.label           as target_label,
                        t.category        as target_category,
                        t.type            as target_type,
                        t.data            as target_data,
                        min(d.edge_depth) as depth
        FROM            descendants d
        INNER JOIN      nodes s
        ON              d.edge_source_id = s.id
        INNER JOIN      nodes t
        ON              d.edge_target_id = t.id
        GROUP BY        d.project_id,
                        d.edge_id,
                        d.edge_label,
                        d.edge_data,
                        d.edge_source_id,
                        s.label,
                        s.category,
                        s.type,
                        s.data,
                        d.edge_target_id,
                        t.label,
                        t.category,
                        t.type,
                        t.data
        ORDER BY        depth,
                        d.project_id;
        ';
        $params = [':project_id' => $projectId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("project Graph fetched", ['params' => $params, 'rows' => $rows]);
        $nodes = [];
        $edges = [];
        foreach ($rows as $row) {
            $nodes[] = new NodeDTO(
                $row['edge_source_id'], 
                $row['source_label'], 
                $row['source_category'],
                $row['source_type'],
                json_decode($row['source_data'], true)
            );

            $nodes[] = new NodeDTO(
                $row['edge_target_id'], 
                $row['target_label'], 
                $row['target_category'],
                $row['target_type'],
                json_decode($row['target_data'], true)
            );

            $edges[] = new EdgeDTO(
                $row['edge_id'],
                $row['edge_source_id'],
                $row['edge_target_id'],
                $row['edge_label'],
                json_decode($row['edge_data'], true)
            );
        }

        return new GraphDTO($nodes, $edges);
    }

    /**
     * @return array<StatusDTO>
     */
    public function getProjectStatus(string $projectId): array
    {
        $this->logger->debug("fetching project status", ['project_id' => $projectId]);
        $sql = '
        WITH RECURSIVE descendants AS (
            SELECT      e.source as edge_source_id,
                        e.target as edge_target_id,
                        0        as edge_depth
            FROM        edges e
            INNER JOIN  nodes_projects np
            ON          e.source = np.node_id
            INNER JOIN  projects p
            ON          np.project_id = p.id
            WHERE       p.id = :project_id
            UNION ALL
            SELECT      e.source         as edge_source_id,
                        e.target         as edge_target_id,
                        d.edge_depth + 1 as edge_depth
            FROM        descendants d
            INNER JOIN  edges e ON d.edge_target_id = e.source
        )
        SELECT DISTINCT d.edge_source_id,
                        COALESCE(s.status, \'unknown\') as edge_source_status,
                        d.edge_target_id,
                        COALESCE(t.status, \'unknown\') as edge_target_status,
                        min(d.edge_depth) as depth
        FROM            descendants d
        LEFT JOIN       status s
        ON              d.edge_source_id = s.node_id
        LEFT JOIN       status t
        ON              d.edge_target_id = t.node_id
        GROUP BY        d.edge_source_id,
                        d.edge_target_id
        ORDER BY        depth;
        ';
        $params = [':project_id' => $projectId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("project Graph fetched", ['params' => $params, 'rows' => $rows]);
        $status = [];
        foreach ($rows as $row) {
            $s = new StatusDTO($row['edge_source_id'], $row['edge_source_status']);
            $t = new StatusDTO($row['edge_target_id'], $row['edge_target_status']);
            $status[] = $s;
            $status[] = $t;
        }
        return $status;
    }

    /**
     * @return ProjectDTO[]
     */
    public function getProjects(): array
    {
        $this->logger->debug("fetching projects");
        $sql = "SELECT * FROM projects";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $projects = [];
        foreach ($rows as $row) {
            $row['data'] = json_decode($row['data'], true);
            $projects[] = new ProjectDTO(
                $row['id'],
                $row['name'],
                $row['author'],
                new DateTimeImmutable($row['created_at']),
                new DateTimeImmutable($row['updated_at']),
                $row['data']
            );
        }
        $this->logger->info("projects fetched", ['rows' => $rows]);
        return $projects;
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

    public function insertProjectNode(string $projectId, string $nodeId): bool
    {
        $this->logger->debug('inserting project node', ['project_id' => $projectId, 'node_id' => $nodeId]);
        $sql = "INSERT INTO nodes_projects (project_id, node_id) VALUES (:project_id, :node_id)";
        $params = [':project_id' => $projectId, ':node_id' => $nodeId];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("project node inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Project node already exists", ['params' => $params]);
                return false;
            }
            throw new DatabaseException("Failed to insert project node. " . $complementary, $exception);
        }
    }

    public function deleteProjectNode(string $projectId, string $nodeId): bool
    {
        $this->logger->debug('deleting project node', ['project_id' => $projectId, 'node_id' => $nodeId]);
        $sql = "DELETE FROM nodes_projects WHERE project_id = :project_id AND node_id = :node_id";
        $params = [':project_id' => $projectId, ':node_id' => $nodeId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project node deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("project node not deleted", ['params' => $params]);
        return false;
    }

    /**
     * @return LogDTO[]
     */
    public function getLogs(int $limit): array
    {
        $this->logger->debug("fetching logs", ['limit' => $limit]);
        $sql = "SELECT * FROM audit ORDER BY created_at DESC LIMIT :limit";
        $params = [':limit' => $limit];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $logs = [];
        foreach ($rows as $row) {
            $logs[] = new LogDTO(
                $row['entity_type'],
                $row['entity_id'],
                $row['action'],
                $row['old_data'] !== null ? json_decode($row['old_data'], true) : null,
                $row['new_data'] !== null ? json_decode($row['new_data'], true) : null,
                $row['user_id'],
                $row['ip_address'],
                new DateTimeImmutable($row['created_at'])
            );
        }

        $this->logger->info("logs fetched", ['params' => $params, 'rows' => $rows]);
        return $logs;
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

    private function wouldCreateCycle(string $source, string $target): bool
    {
        if ($source === $target) {
            return true;
        }

        $sql = '
        WITH RECURSIVE path AS (
            SELECT target as node_id, 1 as depth
            FROM edges
            WHERE source = :target
            UNION ALL
            SELECT e.target, p.depth + 1
            FROM edges e
            INNER JOIN path p ON e.source = p.node_id
        )
        SELECT 1 FROM path WHERE node_id = :source LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':target' => $target, ':source' => $source]);

        return $stmt->fetch() !== false;
    }

    private function initSchema(): void
    {
        $this->pdo->exec($this->sqlSchema);
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