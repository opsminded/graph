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
        $this->logger->error("user not found", ['params' => $params]);
        return null;
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

    public function getNode(string $id): ?array
    {
        $this->logger->debug("fetching node", ['id' => $id]);
        $sql = "SELECT * FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row[ModelNode::NODE_KEYNAME_USERCREATED] = (bool)$row[ModelNode::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("node fetched", ['params' => $params, 'row' => $row]);
            return $row;
        }
        $this->logger->error("node not found", ['params' => $params]);
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug("fetching nodes");
        $sql = "SELECT * FROM nodes";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row[ModelNode::NODE_KEYNAME_USERCREATED] = (bool)$row[ModelNode::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("nodes fetched", ['rows' => $rows]);
        return $rows;
    }

    public function getNodeParentOf(string $id): ?array
    {
        $this->logger->debug("fetching parent node");
        $sql = "SELECT n.* FROM nodes n INNER JOIN edges e ON n.id = e.source WHERE e.target = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row[ModelNode::NODE_KEYNAME_USERCREATED] = (bool)$row[ModelNode::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("parent node fetched", ['row' => $row]);
            return $row;
        }
        $this->logger->error("parent node not found", ['id' => $id]);
        return null;
    }

    public function getDependentNodesOf(string $id): array
    {
        $this->logger->debug("fetching dependent nodes");
        $sql = "SELECT n.* FROM nodes n INNER JOIN edges e ON n.id = e.target WHERE e.source = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row[ModelNode::NODE_KEYNAME_USERCREATED] = (bool)$row[ModelNode::NODE_KEYNAME_USERCREATED];
            $row['data'] = json_decode($row['data'], true);
        }
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
        $this->logger->error("edge not found", ['params' => $params]);
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

    public function getSaves(): array
    {
        $this->logger->debug("fetching saves");
        $sql = "SELECT * FROM saves";
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("saves fetched", ['rows' => $rows]);
        return $rows;
    }

    public function insertSave(string $id, string $name, string $creator, array $data): bool
    {
        $this->logger->debug("inserting new save", ['id' => $id, 'name' => $name, 'creator' => $creator, 'data' => $data]);
        $sql = "INSERT INTO saves (id, name, creator, data) VALUES (:id, :name, :creator, :data)";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':name' => $name, ':creator' => $creator, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("save inserted", ['params' => $params]);
        return true;
    }

    public function updateSave(string $id, string $name, string $creator, array $data): bool
    {
        $this->logger->debug("updating save", ['id' => $id, 'name' => $name, 'creator' => $creator, 'data' => $data]);
        $sql = "UPDATE saves SET name = :name, creator = :creator, data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':name' => $name, ':creator' => $creator, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("save updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("save not updated", ['params' => $params]);
        return false;
    }

    public function deleteSave(string $id): bool
    {
        $this->logger->debug("deleting save", ['id' => $id]);
        $sql = "DELETE FROM saves WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("save deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("save not deleted", ['params' => $params]);
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
            )');

        $this->pdo->exec('INSERT OR IGNORE INTO users VALUES(\'admin\', \'admin\')');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                shape TEXT NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL
            )');
        
        $this->pdo->exec("INSERT OR IGNORE INTO categories VALUES
            ('business',       'Business',       'round-rectangle', 50, 50),
            ('application',    'Application',    'ellipse', 60, 60),
            ('infrastructure', 'Infrastructure', 'round-hexagon', 60, 53)");
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS types (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL
            )');

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
            )');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS edges (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL DEFAULT "not defined",
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                data TEXT,
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            )');

        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target)');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS status (
                node_id    TEXT PRIMARY KEY NOT NULL,
                status     TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            )');
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS saves (
                id         TEXT PRIMARY KEY NOT NULL,
                name       TEXT NOT NULL,
                creator    TEXT NOT NULL,
                data       TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)');

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
            )');
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