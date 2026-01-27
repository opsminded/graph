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

    public function getUser(string $id): ?array
    {
        $this->logger->debug("getting user id", ['id' => $id]);
        $sql = "SELECT id, user_group as \"group\" FROM users WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("user found", ['params' => $params, 'row' => $row]);
            return $row;
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
        return $rows;
    }

    public function insertUser(string $id, string $group): bool
    {
        $this->logger->debug("inserting new user", ['id' => $id, 'group' => $group]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $params = [':id' => $id, ':group' => $group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    public function batchInsertUsers(array $users): bool
    {
        $this->logger->debug("batch inserting users", ['users' => $users]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($users as $user) {
            $params = [':id' => $user['id'], ':group' => $user['group']];
            $stmt->execute($params);
        }
        $this->logger->info("batch users inserted", ['users' => $users]);
        return true;
    }

    public function updateUser(string $id, string $group): bool
    {
        $this->logger->debug("updating new user", ['id' => $id, 'group' => $group]);
        $sql = "UPDATE users SET user_group = :group WHERE id = :id";
        $params = [':id' => $id, ':group' => $group];
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

    public function getCategory(string $id): ?array
    {
        $this->logger->debug("fetching category", ['id' => $id]);
        $sql = "SELECT * FROM categories WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("category fetched", ['params' => $params, 'row' => $row]);
            return $row;
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
        return $rows;
    }

    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool
    {
        $this->logger->debug("inserting new category", ['id' => $id, 'name' => $name]);
        $sql = "INSERT INTO categories (id, name, shape, width, height) VALUES (:id, :name, :shape, :width, :height)";
        $params = [':id' => $id, ':name' => $name, ':shape' => $shape, ':width' => $width, ':height' => $height];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("category inserted", ['params' => $params]);
        return true;
    }

    public function updateCategory(string $id, string $name, string $shape, int $width, int $height): bool
    {
        $this->logger->debug("updating category", ['id' => $id, 'name' => $name, 'shape' => $shape, 'width' => $width, 'height' => $height]);
        $sql = "UPDATE categories SET name = :name, shape = :shape, width = :width, height = :height WHERE id = :id";
        $params = [':id' => $id, ':name' => $name, ':shape' => $shape, ':width' => $width, ':height' => $height];
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

    public function getType(string $id): ?array
    {
        $this->logger->debug("fetching type", ['id' => $id]);
        $sql = "SELECT * FROM types WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("type fetched", ['params' => $params, 'row' => $row]);
            return $row;
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
        return $rows;
    }

    public function insertType(string $id, string $name): bool
    {
        $this->logger->debug("inserting new type", ['id' => $id, 'name' => $name]);
        $sql = "INSERT INTO types (id, name) VALUES (:id, :name)";
        $params = [':id' => $id, ':name' => $name];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("type inserted", ['params' => $params]);
        return true;
    }

    public function updateType(string $id, string $name): bool
    {
        $this->logger->debug("updating type", ['id' => $id, 'name' => $name]);
        $sql = "UPDATE types SET name = :name WHERE id = :id";
        $params = [':id' => $id, ':name' => $name];
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

    public function getNode(string $id): ?array
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
            return $row;
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
        return $rows;
    }

    public function getDependentNodesOf(array $ids): array
    {
        $this->logger->debug("fetching dependent nodes");

        if(empty($ids)) {
            $this->logger->info("no ids provided for dependent nodes fetch");
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $placeholders[] = ":id{$index}";
            $params[":id{$index}"] = $id;
        }
        $placeholdersStr = implode(', ', $placeholders);

        $sql = "
        WITH RECURSIVE descendants AS (
            SELECT id, id as root_id, 0 as depth
            FROM nodes
            WHERE id IN ($placeholdersStr)
            UNION ALL
            
            SELECT n.id, d.root_id, d.depth + 1
            FROM nodes n
            INNER JOIN edges e ON n.id = e.target
            INNER JOIN descendants d ON e.source = d.id
        )
        SELECT     d.id,
                   d.depth
        FROM       descendants d
        ORDER BY   d.depth,
                   d.id;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("dependent nodes fetched", ['rows' => $rows]);
        return $rows;
    }

    public function insertNode(string $id, string $label, string $category, string $type, bool $userCreated = false, array $data = []): bool
    {
        $this->logger->debug("inserting new node", ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'userCreated' => $userCreated, 'data' => $data]);
        $sql = "INSERT INTO nodes (id, label, category, type, user_created, data) VALUES (:id, :label, :category, :type, :user_created, :data)";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':label' => $label, ':category' => $category, ':type' => $type, ':user_created' => $userCreated, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("node inserted", ['params' => $params]);
        return true;
    }

    public function batchInsertNodes(array $nodes): bool
    {
        $this->logger->debug("batch inserting nodes", ['nodes' => $nodes]);
        $sql = "INSERT INTO nodes (id, label, category, type, user_created, data) VALUES (:id, :label, :category, :type, :user_created, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($nodes as $node) {
            $data = json_encode($node['data'] ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $node['id'],
                ':label' => $node['label'],
                ':category' => $node['category'],
                ':type' => $node['type'],
                ':user_created' => $node['user_created'] ?? false,
                ':data' => $data
            ];
            $stmt->execute($params);
        }
        $this->logger->info("batch nodes inserted", ['nodes' => $nodes]);
        return true;
    }

    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        $this->logger->debug("updating node", ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'data' => $data]);
        $sql = "UPDATE nodes SET label = :label, category = :category, type = :type, data = :data WHERE id = :id";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':label' => $label, ':category' => $category, ':type' => $type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("node updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("node not updated", ['params' => $params]);
        return false;
    }

    public function batchUpdateNodeStatus(array $statuses): bool
    {
        $this->logger->debug("batch updating node statuses", ['statuses' => $statuses]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($statuses as $status) {
            $params = [':node_id' => $status['node_id'], ':status' => $status['status']];
            $stmt->execute($params);
        }
        $this->logger->info("batch node statuses updated", ['statuses' => $statuses]);
        return true;
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

    public function getEdge(string $source, string $target): ?array
    {
        $this->logger->debug("getting edge", ['source' => $source, 'target' => $target]);
        $sql = "SELECT * FROM edges WHERE source = :source AND target = :target";
        $params = [':source' => $source, ':target' => $target];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("edge found", ['params' => $params, 'row' => $row]);
            return $row;
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

    public function insertEdge(string $id, string $source, string $target, string $label, array $data = []): bool
    {
        $this->logger->debug("inserting edge", ['id' => $id, 'source' => $source, 'target' => $target, 'label' => $label, 'data' => $data]);
        $edgeData = $this->getEdge($target, $source);
        if (! is_null($edgeData)) {
            $this->logger->error("cicle detected", $edgeData);
            return false;
        }
        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':source' => $source, ':target' => $target, ':label' => $label, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("edge inserted", ['params' => $params]);
        return true;
    }

    public function batchInsertEdges(array $edges): bool
    {
        $this->logger->debug("batch inserting edges", ['edges' => $edges]);
        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($edges as $edge) {
            $edgeData = $this->getEdge($edge['target'], $edge['source']);
            if (! is_null($edgeData)) {
                $this->logger->error("cicle detected", $edgeData);
                continue;
            }
            $data = json_encode($edge['data'] ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $edge['id'],
                ':source' => $edge['source'],
                ':target' => $edge['target'],
                ':label' => $edge['label'],
                ':data' => $data
            ];
            $stmt->execute($params);
        }
        $this->logger->info("batch edges inserted", ['edges' => $edges]);
        return true;
    }

    public function updateEdge(string $id, string $label, array $data = []): bool
    {
        $this->logger->debug("updating edge", ['id' => $id, 'label' => $label, 'data' => $data]);
        $sql = "UPDATE edges SET label = :label, data = :data WHERE id = :id";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':label' => $label, ':data' => $data];
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

    public function getNodeStatus(string $id): ?array
    {
        $this->logger->debug("fetching node status", ['id' => $id]);
        $sql = "SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id WHERE n.id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if($row) {
            $this->logger->info("node status fetched", ['params' => $params, 'row' => $row]);
            return $row;
        }
        return null;
    }

    public function updateNodeStatus(string $id, string $status): bool
    {
        $this->logger->debug("updating node status", ['id' => $id, 'status' => $status]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $params = [':node_id' => $id, ':status' => $status];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("node status updated", ['params' => $params]);
        return true;
    }

    public function getProject(string $id): ?array
    {
        $this->logger->debug("fetching project", ['id' => $id]);
        $sql = "SELECT * FROM projects WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {

            $save = [
                'id' => $row['id'],
                'name' => $row['name'],
                'author' => $row['author'],
                'data' => json_decode($row['data'], true),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];

            $depNodes = $this->getDependentNodesOf($save['data']['nodes'] ?? []);
            
            unset($save['data']);

            $save['nodes'] = [];
            foreach($depNodes as $depNode) {
                $save['nodes'][] = $depNode['id'];
            }

            $this->logger->info("project fetched", ['params' => $params, 'row' => $row]);
            return $save;
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

    public function insertProject(string $id, string $name, string $author, array $data): bool
    {
        $this->logger->debug("inserting new project", ['id' => $id, 'name' => $name, 'author' => $author, 'data' => $data]);
        $sql = "INSERT INTO projects (id, name, author, data) VALUES (:id, :name, :author, :data)";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':name' => $name, ':author' => $author, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("project inserted", ['params' => $params]);
        return true;
    }

    public function updateProject(string $id, string $name, string $author, array $data): bool
    {
        $this->logger->debug("updating project", ['id' => $id, 'name' => $name, 'author' => $author, 'data' => $data]);
        $sql = "UPDATE projects SET name = :name, author = :author, data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':name' => $name, ':author' => $author, ':data' => $data];
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

    public function getSuccessors(string $id): array
    {
        $this->logger->debug("fetching successors", ['id' => $id]);
        $sql = "
        WITH RECURSIVE successors AS (
            SELECT id, 0 as depth
            FROM nodes
            WHERE id = :id
            UNION ALL
            SELECT n.id, s.depth + 1
            FROM nodes n
            INNER JOIN edges e ON n.id = e.target
            INNER JOIN successors s ON e.source = s.id
        )
        SELECT     id,
                   depth
        FROM       successors
        WHERE      id != :id
        ORDER BY   depth,
                   id;
        ";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("successors fetched", ['params' => $params, 'rows' => $rows]);
        return $rows;
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

    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool
    {
        $this->logger->debug("inserting audit log", ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'action' => $action, 'old_data' => $old_data, 'new_data' => $new_data, 'user_id' => $user_id, 'ip_address' => $ip_address]);
        $sql = "INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address) VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)";
        $old_data = $old_data !== null ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null;
        $new_data = $new_data !== null ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null;
        $params = [':entity_type' => $entity_type, ':entity_id' => $entity_id, ':action' => $action, ':old_data' => $old_data, ':new_data' => $new_data, ':user_id' => $user_id, ':ip_address' => $ip_address];
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

        $this->pdo->exec('INSERT OR IGNORE INTO users VALUES(\'admin\', \'admin\')');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                shape TEXT NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL
            );
        ');
        
        $this->pdo->exec("INSERT OR IGNORE INTO categories VALUES
            ('business',       'Business',       'round-rectangle', 80, 80),
            ('application',    'Application',    'ellipse', 60, 60),
            ('infrastructure', 'Infrastructure', 'round-hexagon', 60, 53)");
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS types (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL
            );
        ');

        $this->pdo->exec("INSERT OR IGNORE INTO types VALUES
            ('business', 'Business'),
            ('business_case', 'Business Case'),
            ('service', 'Service'),
            ('server', 'Server'),
            ('database', 'Database')");

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
            CREATE TABLE IF NOT EXISTS project_nodes (
                project_id TEXT NOT NULL,
                node_id    TEXT NOT NULL,
                PRIMARY KEY (project_id, node_id),
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
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