<?php

declare(strict_types=1);

final class User
{
    public string $id;
    public Group $group;

    public function __construct(string $id, Group $group)
    {
        $this->id = $id;
        $this->group = $group;
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'group' => $this->group->toArray(),
        ];
    }
}

final class Group
{
    private const ALLOWED_GROUPS = ['anonymous', 'consumer', 'contributor', 'admin'];
    
    public string $id;
    
    public function __construct(string $id)
    {
        if (!in_array($id, self::ALLOWED_GROUPS, true)) {
            throw new InvalidArgumentException("Invalid user group: {$id}");
        }
        $this->id  = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id
        ];
    }
}

final class Graph
{
    public array $nodes = [];
    public array $edges = [];
    public array $data  = [];
    public array $layout = [];
    public array $styles = [];

    public function __construct(array $nodes, array $edges)
    {
        $this->nodes = $nodes;
        $this->edges = $edges;
    }

    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'data' => $this->data,
            'layout' => $this->layout,
            'styles' => $this->styles,
        ];
    }
}

final class Node
{
    private const ALLOWED_CATEGORIES  = ['business', 'application', 'network', 'infrastructure'];
    private const ALLOWED_TYPES       = ['server', 'database', 'application'];
    private const ID_VALIDATION_REGEX = '/^[a-zA-Z0-9\-_]+$/';
    private const LABEL_MAX_LENGTH    = 20;
    
    public string $id;
    public string $label;
    public string $category;
    public string $type;

    public array $data = [];

    public function __construct(string $id, string $label, string $category, string $type, array $data)
    {
        $this->validate($id, $label, $category, $type);
        $this->id       = $id;
        $this->label    = $label;
        $this->category = $category;
        $this->type     = $type;
        $this->data     = $data;
    }

    private function validate(string $id, string $label, string $category, string $type): void
    {
        if (!preg_match(self::ID_VALIDATION_REGEX, $id)) {
            throw new InvalidArgumentException("Invalid node ID: {$id}");
        }

        if (strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException("Node label exceeds maximum length of " . self::LABEL_MAX_LENGTH);
        }

        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            throw new InvalidArgumentException("Invalid node category: {$category}");
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Invalid node type: {$type}");
        }
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'category' => $this->category,
            'type'     => $this->type,
            'data'     => $this->data
        ];
    }
}

final class NodeStatus
{
    private const ALLOWED_NODE_STATUSES = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

    public string $nodeId;
    public string $status;

    public function __construct(string $nodeId, string $status)
    {
        if (!in_array($status, self::ALLOWED_NODE_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid node status: {$status}");
        }
        $this->nodeId = $nodeId;
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'node_id' => $this->nodeId,
            'status'  => $this->status,
        ];
    }
}

final class NodeStatuses
{
    public array $statuses = [];

    public function addStatus(NodeStatus $status): void
    {
        $this->statuses[] = $status;
    }
}

final class Nodes
{
    public array $nodes = [];

    public function addNode(Node $node): void
    {
        $this->nodes[] = $node;
    }
}

final class Edge
{
    public ?string $id;
    public string $source;
    public string $target;
    public array $data;

    public function __construct(?string $id = null, string $source, string $target, array $data = [])
    {
        $this->id     = $id;
        $this->source = $source;
        $this->target = $target;
        $this->data   = $data;
    }

    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'source' => $this->source,
            'target' => $this->target,
            'data'   => $this->data
        ];
    }
}

final class Edges
{
    public array $edges = [];
    public function addEdge(Edge $edge): void
    {
        $this->edges[] = $edge;
    }
}

final class AuditLog
{
    public string $entityType;
    public string $entityId;
    public string $action;
    public ?array $oldData;
    public ?array $newData;
    public string $userId;
    public string $ipAddress;
    public string $createdAt;

    public function __construct(string $entityType, string $entityId, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->action     = $action;
        $this->oldData    = $oldData;
        $this->newData    = $newData;
    }
}

final class AuditLogs
{
    public array $logs = [];
    public function addLog(AuditLog $log): void
    {
        $this->logs[] = $log;
    }
}

interface LoggerInterface
{
    public function info(string $message, array $data = []): void;
    public function debug(string $message, array $data = []): void;
    public function error(string $message, array $data = []): void;
}

final class Logger implements LoggerInterface
{
    private string $fileName;
    private $fd;

    public function __construct($file_name)
    {
        $this->fileName = $file_name;
        $this->fd = fopen($this->fileName, 'a');
    }

    public function info(string $message, array $data = []): void
    {
        $this->log('INFO', $message, $data);
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log('DEBUG', $message, $data);
    }

    public function error(string $message, array $data = []): void
    {
        $this->log('ERROR', $message, $data);
    }

    public function databaseException(string $message, array $data = [], ?Exception $e = null, ?string $query = null, ?array $params = null): DatabaseException
    {
        $this->error($message, $data);
        return new DatabaseException($message, 0, $e, $query, $params);
    }

    private function log(string $type, $message, $data = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$type}] {$method}: $message ($data)\n";
        fwrite($this->fd, $message);
    }
}

interface GraphDatabaseInterface
{
    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): void;
    public function updateUser(string $id, string $group): void;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): void;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): void;
    public function deleteNode(string $id): void;

    public function getEdge(string $source, string $target): ?array;
    public function getEdgeById(string $id): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, array $data = []): void;
    public function updateEdge(string $id, string $source, string $target, array $data = []): void;
    public function deleteEdge(string $id): void;

    public function getStatuses(): array;
    public function getNodeStatus(string $id): array;
    public function updateNodeStatus(string $id, string $status): void;

    public function getLogs(int $limit): array;
    public function insertAuditLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): void;
}

final class DatabaseException extends RuntimeException
{
    public ?string $query;
    public ?array $params;
    
    public function __construct(string $message = "",  int $code = 0, ?Throwable $previous = null, ?string $query = null, ?array $params = null) {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
        $this->params = $params;
    }
}

final class GraphDatabase implements GraphDatabaseInterface
{
    private PDO $pdo;
    private Logger $logger;

    public function __construct(PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->initSchema();
    }

    public function getUser(string $id): ?array
    {
        $this->logger->debug("getting user id", ['id' => $id]);
        
        $sql = "SELECT * FROM users WHERE id = :id";
        $params = [':id' => $id];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            if ($row) {
                $this->logger->info("user found", ['params' => $params, 'row' => $row]);
                return $row;
            }
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getUser: ' . $e->getMessage(), $params, $e, $sql, $params);
        }
        $this->logger->info("user not found", ['params' => $params]);
        return null;
    }

    public function insertUser(string $id, string $group): void
    {
        $this->logger->debug('inserting new user', ['id' => $id, 'group' => $group]);

        $sql = "
                INSERT OR IGNORE INTO users (id, user_group)
                VALUES (:id, :group)";
            
        $params = [
            ':id'       => $id,
            ':group'    => $group
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info('user inserted', ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in insertUser', $params, $e, $sql, $params);
        }
    }

    public function updateUser(string $id, string $group): void
    {
        $this->logger->debug('updating new user', ['id' => $id, 'group' => $group]);

        $sql = "
                UPDATE users
                SET    user_group = :group
                WHERE  id = :id";

        $params = [
            ':id'       => $id,
            ':group'    => $group
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info('user updated', ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in updateUser', $params, $e, $sql, $params);
        }
    }

    public function getNode(string $id): ?array
    {
        $this->logger->debug("fetching node", ['id' => $id]);
        
        $sql = "SELECT * FROM nodes WHERE id = :id";
        $params = [':id' => $id];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            if ($row) {
                $row['data'] = json_decode($row['data'], true);
                $this->logger->info("node fetched", ['params' => $params, 'row' => $row]);
                return $row;
            }
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getNode', $params, $e, $sql, $params);
        }
        $this->logger->info("node not found", ['params' => $params]);
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug("fetching nodes");

        $sql = "SELECT * FROM nodes";
        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();
            foreach($rows as &$row) {
                $row['data'] = json_decode($row['data'], true);
            }
            $this->logger->info("nodes fetched", ['rows' => $rows]);
            return $rows;
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getNodes', [], $e, $sql, null);
        }
    }

    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): void
    {
        $this->logger->debug("inserting new node", ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'data' => $data]);

        $sql = "
            INSERT OR IGNORE INTO nodes (id, label, category, type, data) 
            VALUES (:id, :label, :category, :type, :data)";

        $params = [
            ':id'       => $id,
            ':label'    => $label,
            ':category' => $category,
            ':type'     => $type,
            ':data'     => json_encode($data, JSON_UNESCAPED_UNICODE)
        ];

        try {            
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info("node inserted", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in insertNode', $params, $e, $sql, $params);
        }
    }

    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): void
    {
        $this->logger->debug("updating node", ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'data' => $data]);

        $sql = "
            UPDATE nodes
            SET    label = :label, category = :category, type = :type, data = :data
            WHERE  id = :id";
        
        $params = [
            ':id'       => $id,
            ':label'    => $label,
            ':category' => $category,
            ':type'     => $type,
            ':data'     => json_encode($data, JSON_UNESCAPED_UNICODE)
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->logger->info("node updated", ['params' => $params]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in updateNode', $params, $e, $sql, $params);
        }
    }

    public function deleteNode(string $id): void
    {
        $this->logger->debug("deleting node", ['id' => $id]);

        $sql = "DELETE FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->debug("node deleted", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in deleteNode', $params, $e, $sql, $params);
        }
    }

    public function getEdge(string $source, string $target): ?array
    {
        $this->logger->debug("getting edge", ['source' => $source, 'target' => $target]);

        $sql = "
            SELECT * FROM edges
            WHERE source = :source AND target = :target";
        
        $params = [
            ':source' => $source,
            ':target' => $target
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();

            if ($row) {
                $row['data'] = json_decode($row['data'], true);
                $this->logger->info("edge found", ['params' => $params, 'row' => $row]);
                return $row;
            }
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getEdge', $params, $e, $sql, $params);
        }
        $this->logger->error('edge not found', ['params' => $params]);
        return null;
    }

    public function getEdgeById(string $id): ?array
    {
        $this->logger->debug("getting edge by Id", ['id' => $id]);

        $sql = "
            SELECT * FROM edges
            WHERE id = :id";
        
        $params = [
            ':id' => $id,
        ];

        try {
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
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getEdgeById', $params, $e, $sql, $params);
        }
    }

    public function getEdges(): array
    {
        $this->logger->debug("fetching edges");

        $sql = "SELECT * FROM edges";

        try {
            $stmt  = $this->pdo->query($sql);
            $rows  = $stmt->fetchAll();
            foreach($rows as &$row) {
                $row['data'] = json_decode($row['data'], true);
            }
            $this->logger->info("edges fetched", ['rows' => $rows]);
            return $rows;
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getEdges', [], $e, $sql);
        }
    }

    public function insertEdge(string $id, string $source, string $target, array $data = []): void
    {
        $this->logger->debug("inserting edge", ['id' => $id, 'source' => $source, 'target' => $target, 'data' => $data]);

        try {
            $edgeData = $this->getEdge($target, $source);
            if (! is_null($edgeData)) {
                $this->logger->error("cicle detected", $edgeData);
                return;
            }
        
            $sql = "
                INSERT OR IGNORE INTO edges(id, source, target, data)
                VALUES (:id, :source, :target, :data)";

            $params = [
                ':id'     => $id,
                ':source' => $source,
                ':target' => $target,
                ':data'   => json_encode($data, JSON_UNESCAPED_UNICODE)
            ];

            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info("edge inserted", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in insertEdge', [], $e);
        } catch (DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw $this->logger->databaseException('DatabaseException in insertEdge: ' . $comp, [], $e);
        }
    }

    public function updateEdge(string $id, string $source, string $target, array $data = []): void
    {
        $this->logger->debug("updating edge", ['id' => $id, 'source' => $source, 'target' => $target, 'data' => $data]);

        $sql = "
            UPDATE edges
            SET    source = :source, target = :target, data = :data
            WHERE  id = :id";
        
        $params = [
            ':id'   => $id,
            'source' => $source,
            'target' => $target,
            ':data' => json_encode($data, JSON_UNESCAPED_UNICODE)
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info("edge updated", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in updateEdge', $params, $e, $sql, $params);
        }
    }

    public function deleteEdge(string $id): void
    {
        $this->logger->debug("deleting edge", ['id' => $id]);

        $sql = "DELETE FROM edges WHERE id = :id";
        $params = [':id' => $id];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info("edge deleted", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in deleteEdge', $params, $e, $sql, $params);
        }
    }

    public function getStatuses(): array
    {
        $this->logger->debug("fetching statuses");
        $sql = "
            SELECT n.id, s.status
            FROM   nodes n
            LEFT JOIN status s ON n.id = s.node_id";
        
        try {
            $stmt   = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();
            $this->logger->info("statuses fetched", ['rows' => $rows]);
            return $rows;
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getStatuses', [], $e, $sql);
        }
    }

    public function getNodeStatus(string $id): array
    {
        $this->logger->debug("fetching node status", ['id' => $id]);

        $sql = "
                SELECT    n.id, s.status
                FROM      nodes n
                LEFT JOIN status s
                ON        n.id = s.node_id
                WHERE     n.id = :id";
        
        $params = [':id' => $id];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            $this->logger->info("node status fetched", ['params' => $params, 'row' => $row]);
            return $row;
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getNodeStatus', $params, $e, $sql, $params);
        }
    }

    public function updateNodeStatus(string $id, string $status): void
    {
        $this->logger->debug("updating node status", ['id' => $id, 'status' => $status]);

        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        
        $params = [
            ':node_id' => $id,
            ':status'  => $status
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info("node status updated", ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in updateNodeStatus', $params, $e, $sql, $params);
        }
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug("fetching logs", ['limit' => $limit]);
        $sql = "
            SELECT *
            FROM audit
            ORDER BY created_at DESC
            LIMIT :limit";
        
        $params = [
            ':limit' => $limit
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
            $this->logger->info("logs fetched", ['params' => $params, 'rows' => $rows]);
            return $rows;
        } catch (PDOException $e) {
            throw $this->logger->databaseException('PDO Exception in getLogs', $params, $e, $sql, $params);
        }
    }

    public function insertAuditLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): void
    {
        $this->logger->debug("inserting audit log", ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'action' => $action, 'old_data' => $old_data, 'new_data' => $new_data, 'user_id' => $user_id, 'ip_address' => $ip_address]);

        $sql = "INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address)
            VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)";
        
        $params = [
            ':entity_type' => $entity_type,
            ':entity_id'   => $entity_id,
            ':action'      => $action,
            ':old_data'    => $old_data !== null ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null,
            ':new_data'    => $new_data !== null ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null,
            ':user_id'     => $user_id,
            ':ip_address'  => $ip_address
        ];

        try {
            $stmt = $this->pdo->prepare($sql);
            $executed = $stmt->execute($params);
            $this->logger->info('audit log inserted', ['params' => $params, 'executed' => $executed]);
        } catch (PDOException $e) {
           throw $this->logger->databaseException('PDO Exception in insertAuditLog', $params, $e, $sql, $params);
        }
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                user_group TEXT NOT NULL
            )");

        $this->pdo->exec("INSERT INTO users VALUES('admin', 'admin')");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS nodes (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL,
                category TEXT NOT NULL,
                type TEXT NOT NULL,
                data TEXT NOT NULL
            )");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS edges (
                id TEXT PRIMARY KEY,
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                data TEXT,
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            )");

        $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target)");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS status (
                node_id TEXT PRIMARY KEY NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            )");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)");

        $this->pdo->exec("
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
            )");
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

final class SecurityException extends RuntimeException
{
}

final class GraphServiceException extends RuntimeException
{
}

interface GraphServiceInterface
{
    public function getUser(string $id): ?User;
    public function insertUser(User $user): void;
    public function updateUser(User $user): void;

    public function getGraph(): Graph;

    public function getNode(string $id): ?Node;
    public function getNodes(): Nodes;
    public function insertNode(Node $node): void;
    public function updateNode(Node $node): void;
    public function deleteNode(string $id): void;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): Edges;
    public function insertEdge(Edge $edge): void;
    public function updateEdge(Edge $edge): void;
    public function deleteEdge(string $id): void;

    public function getStatuses(): NodeStatuses;
    public function getNodeStatus(string $id): NodeStatus;
    public function updateNodeStatus(NodeStatus $status): void;

    public function getLogs($limit): AuditLogs;
}

final class GraphContext
{
    private static User $user;
    private static ?string $user_ip;

    public static function update(User $user, ?string $user_ip)
    {
        self::$user = $user;
        self::$user_ip = $user_ip;
    }

    public static function getUserId(): string
    {
        return self::$user->id;
    }

    public static function getUserGroup(): string
    {
        return self::$user->group->id;
    }

    public static function getUserIp(): ?string
    {
        return self::$user_ip;
    }
}

final class GraphService implements GraphServiceInterface
{
    private const SECURE_ACTIONS = [
        'GraphService::getUser'          => true,
        'GraphService::getGraph'         => true,
        'GraphService::getNode'          => true,
        'GraphService::getNodes'         => true,
        'GraphService::getEdge'          => true,
        'GraphService::getEdges'         => true,
        'GraphService::getStatuses'      => true,
        'GraphService::getNodeStatus'    => true,
        'GraphService::updateNodeStatus' => true,
        'GraphService::getLogs'          => true,

        'GraphService::insertUser'       => false,
        'GraphService::updateUser'       => false,

        'GraphService::insertNode'       => false,
        'GraphService::updateNode'       => false,
        'GraphService::deleteNode'       => false,
        'GraphService::insertEdge'       => false,
        'GraphService::updateEdge'       => false,
        'GraphService::deleteEdge'       => false,
        'GraphService::insertAuditLog'   => false,
    ];

    private GraphDatabaseInterface $db;
    private Logger $logger;

    public function __construct(GraphDatabaseInterface $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getUser(string $id): ?User
    {
        try {
            $this->verify();
            $data = $this->db->getUser($id);
            if (! is_null($data)) {
                $g = new Group($data['user_group']);
                $user = new User($id, $g);
                return $user;
            }
            return null;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = $e->getMessage() . " ({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("insertUser exception: " . $comp, 0, $e);
        }
    }

    public function insertUser(User $user): void
    {
        try {
            $this->verify();
            $this->db->insertUser($user->id, $user->group->id);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("insertUser exception: " . $comp, 0, $e);
        }
    }

    public function updateUser(User $user): void
    {
        try {
            $this->verify();
            $this->db->updateUser($user->id, $user->group->id);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("updateUser exception: " . $comp, 0, $e);
        }
    }

    public function getGraph(): Graph
    {
        try {
            $this->verify();
            $nodes = $this->getNodes()->nodes;
            $edges = $this->getEdges()->edges;
            return new Graph($nodes, $edges);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getGraph exception: " . $comp, 0, $e);
        }
    }

    public function getNode(string $id): ?Node
    {
        try {
            $this->verify();
            $data = $this->db->getNode($id);
            if (! is_null($data)) {
                return new Node(
                    $data['id'],
                    $data['label'],
                    $data['category'],
                    $data['type'],
                    $data['data']
                );
            }
            return null;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getNode exception: " . $comp, 0, $e);
        }
    }

    public function getNodes(): Nodes
    {
        try {
            $this->verify();
            $nodesData = $this->db->getNodes();
            $nodes     = new Nodes();
            foreach ($nodesData as $data) {
                $node = new Node(
                    $data['id'],
                    $data['label'],
                    $data['category'],
                    $data['type'],
                    $data['data']
                );
                $nodes->addNode($node);
            }
            return $nodes;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getNodes exception: " . $comp, 0, $e);
        }
    }

    public function insertNode(Node $node): void
    {
        $this->logger->debug('inserting node', $node->toArray());

        try {
            $this->verify();
            $this->logger->debug('permission allowed', $node->toArray());
            $this->insertAuditLog(new AuditLog( 'node', $node->id, 'insert', null, $node->toArray()));
            $this->db->insertNode($node->id, $node->label, $node->category, $node->type, $node->data);
            $this->logger->info('node inserted', $node->toArray());
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("insertNode exception: " . $comp, 0, $e);;
        }
    }

    public function updateNode(Node $node): void
    {
        try {
            $this->verify();
            $old = $this->getNode($node->id);
            $this->insertAuditLog(new AuditLog( 'node', $node->id, 'update', $old->toArray(), $node->toArray()));
            $this->db->updateNode($node->id, $node->label, $node->category, $node->type, $node->data);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("updateNode exception: " . $comp, 0, $e);
        }
    }

    public function deleteNode(string $id): void
    {
        try {
            $this->verify();
            $old = $this->getNode($id);
            $this->insertAuditLog(new AuditLog( 'node', $id, 'delete', $old->toArray(), null));
            $this->db->deleteNode($id);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("deleteNode exception: " . $comp, 0, $e);
        }
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        try {
            $this->verify();
            $data = $this->db->getEdge($source, $target);
            if(! is_null($data)) {
                return new Edge(
                    $data['id'],
                    $data['source'],
                    $data['target'],
                    $data['data']
                );
            }
            return null;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getEdge exception: " . $comp, 0, $e);
        }
    }

    public function getEdges(): Edges
    {
        try {
            $this->verify();

            $edgesData = $this->db->getEdges();
            $edges     = new Edges();
            foreach ($edgesData as $data) {
                $edge = new Edge(
                    $data['id'],
                    $data['source'],
                    $data['target'],
                    $data['data']
                );
                $edges->addEdge($edge);
            }
            return $edges;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getEdges exception" . $comp, 0, $e);
        }
    }

    public function insertEdge(Edge $edge): void
    {
        try {
            $this->verify();
            $this->insertAuditLog(new AuditLog( 'edge', $edge->id, 'insert', null, $edge->toArray()));
            $this->db->insertEdge($edge->id, $edge->source, $edge->target, $edge->data);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("insertEdge exception: ". $comp, 0, $e);
        }
    }

    public function updateEdge(Edge $edge): void
    {
        try {
            $this->verify();
            $old = $this->getEdgeById($edge->id);
            if (is_null($old)) {
                throw new GraphServiceException("edge not found", 0, null);
            }
            $this->insertAuditLog(new AuditLog( 'edge', $edge->id, 'update', $old->toArray(), $edge->toArray()));
            $this->db->updateEdge($edge->id, $edge->source, $edge->target, $edge->data);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("updateEdge exception: " . $comp, 0, $e);
        }
    }

    public function deleteEdge(string $id): void
    {
        try {
            $this->verify();
            $this->db->deleteEdge($id);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("deleteEdge exception: " . $comp, 0, $e);
        }
    }

    public function getStatuses(): NodeStatuses
    {
        try {
            $this->verify();

            $statusesData = $this->db->getStatuses();
            $nodeStatuses = new NodeStatuses();
            foreach ($statusesData as $data) {
                $status = new NodeStatus($data['id'], $data['status'] ?? 'unknown');
                $nodeStatuses->addStatus($status);
            }
            return $nodeStatuses;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getStatuses exception: " . $comp, 0, $e);
        }
    }

    public function getNodeStatus(string $id): NodeStatus
    {
        try {
            $this->verify();
            $statusData = $this->db->getNodeStatus($id);
            return new NodeStatus($id, $statusData['status'] ?? 'unknown');
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getNodeStatus exception: " . $comp, 0, $e);
        }
    }

    public function updateNodeStatus(NodeStatus $status): void
    {
        try {
            $this->verify();
            $this->db->updateNodeStatus($status->nodeId, $status->status);
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("updateNodeStatus exception: " . $comp, 0, $e);
        }
    }

    public function getLogs($limit): AuditLogs
    {
        try {
            $this->verify();
            $logs = new AuditLogs();
            $rows = $this->db->getLogs($limit);
            foreach ($rows as $row) {
                $old_data = $row['old_data'] ? json_decode($row['old_data'], true) : [];
                $new_data = $row['new_data'] ?  json_decode($row['new_data'], true) : [];
                $log = new AuditLog(
                    $row['entity_type'],
                    $row['entity_id'],
                    $row['action'],
                    $old_data,
                    $new_data,
                );
                $log->userId    = $row['user_id'];
                $log->ipAddress = $row['ip_address'];
                $log->createdAt = $row['created_at'];
                $logs->addLog($log);
            }
            return $logs;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getLogs exception: " . $comp, 0, $e);
        }
    }

    private function getEdgeById(string $id): ?Edge
    {
        try {
            $data = $this->db->getEdgeById($id);
            if($data) {
                return new Edge(
                    $data['id'],
                    $data['source'],
                    $data['target'],
                    $data['data']
                );
            }
            return null;
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("getEdgeById exception:" . $comp, 0, $e);
        }
    }

    private function insertAuditLog(AuditLog $auditLog): void
    {
        $user_id   = GraphContext::getUserId();
        $ip_address = GraphContext::getUserIp();

        try {
            $this->db->insertAuditLog(
                $auditLog->entityType,
                $auditLog->entityId,
                $auditLog->action,
                $auditLog->oldData,
                $auditLog->newData,
                $user_id,
                $ip_address
            );
        } catch(DatabaseException $e) {
            $params = json_encode($e->params, JSON_UNESCAPED_UNICODE);
            $comp = "({$e->query})" . "(". json_encode($params) . ")";
            throw new GraphServiceException("insertAuditLog exception:" . $comp, 0, $e);
        }
    }

    private function verify(): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";

        $group = GraphContext::getUserGroup();

        // if is admin, allow all
        if ($group === 'admin') {
            return;
        }

        // if action is in the SAFE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            return;
        }
        
        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] == false && $group == 'contributor')
        {
            return;
        }

        throw new GraphServiceException('action not allowed: ' . $action);
    }
}

final class RequestException extends RuntimeException
{
    public array $data;
    public array $params;
    public string $path;
    
    public function __construct($message, array $data, array $params, string $path)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
        $this->params = $params;
        $this->path = $path;
    }
}

final class Request
{
    public array $data;
    public array $params;
    public string $path;
    public string $method;

    public function __construct()
    {
        $this->params = $_GET;

        if(is_null($_SERVER['REQUEST_METHOD'])) {
            throw new RequestException('method not set', [], $this->params, '');
        }

        if(! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'PUT', 'POST', 'DELETE'])) {
            throw new RequestException('method not allowed: ' . $_SERVER['REQUEST_METHOD'], [], $this->params, '');
        }

        $this->method = $_SERVER['REQUEST_METHOD'];

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        $path = str_replace($scriptName, '', $requestUri);
        $this->path = $path;

        $jsonData = file_get_contents('php://input');
        if ($jsonData) {
            $this->data = json_decode($jsonData, true); 
        } else {
            $this->data = [];
        }
    }

    public function getParam($name): string
    {
        if(isset($this->params[$name])) {
            return $this->params[$name];
        }
        throw new RequestException("param '{$name}' not found", $this->data, $this->params, $this->path);
    }

    public function toArray(): array
    {
        return [
            'path'   => $this->path,
            'method' => $this->method,
            'data'   => $this->data,
            'params' => $this->params,
        ];
    }
}

interface ResponseInterface
{
    public function send(): void;
}

class Response implements ResponseInterface
{
    public int $code;
    public string $status;
    public string $message;
    public array $data;

    public function __construct(int $code, string $status, string $message = '', array $data)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->code);
        $this->data = ['code' => $this->code, 'status' => $this->status, 'message' => $this->message, 'data' => $this->data];
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
    }
}

class OKResponse extends Response
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, 'success', $message, $data);
    }
}

class CreatedResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(201, 'success', $message, $data);
    }
}

class BadRequestResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(400, 'error', $message, $data);
    }
}

class UnauthorizedResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(401, 'error', $message, $data);
    }
}

class ForbiddenResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(403, 'error', $message, $data);
    }
}

class NotFoundResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(404, 'error', $message, $data);
    }
}

class InternalServerErrorResponse extends Response
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(500, 'error', $message, $data);
    }
}

interface GraphControllerInterface
{
    public function getUser(Request $req): ResponseInterface;
    public function insertUser(Request $req): ResponseInterface;
    public function updateUser(Request $req): ResponseInterface;

    public function getGraph(Request $req): ResponseInterface;

    public function getNode(Request $req): ResponseInterface;
    public function getNodes(Request $req): ResponseInterface;
    public function insertNode(Request $req): ResponseInterface;
    public function updateNode(Request $req): ResponseInterface;
    public function deleteNode(Request $req): ResponseInterface;

    public function getEdge(Request $req): ResponseInterface;
    public function getEdges(Request $req): ResponseInterface;
    public function insertEdge(Request $req): ResponseInterface;
    public function updateEdge(Request $req): ResponseInterface;
    public function deleteEdge(Request $req): ResponseInterface;

    public function getStatuses(Request $req): ResponseInterface;
    public function getNodeStatus(Request $req): ResponseInterface;
    public function updateNodeStatus(Request $req): ResponseInterface;

    public function getLogs(Request $req): ResponseInterface;
}

final class GraphControllerException extends RuntimeException
{
}

final class GraphController implements GraphControllerInterface
{
    private GraphServiceInterface $service;
    private Logger $logger;

    public function __construct(GraphServiceInterface $service, Logger $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }

    public function getUser(Request $req): ResponseInterface
    {
        try {
            $id = $req->getParam('id');
            $user = $this->service->getUser($id);
            if(is_null($user)) {
                return new NotFoundResponse('User not found', ['id' => $id]);
            }
            return new OKResponse('user found', $user->toArray());
        } catch(RequestException $e) {
            return new BadRequestResponse('bad request: ' . $e->getMessage(), $req->data);
        } catch(GraphServiceException $e) {
            return new InternalServerErrorResponse('user not created: ' . $e->getMessage(), $req->data);
        }
        throw new GraphControllerException('other internal error in getUser');
    }

    public function insertUser(Request $req): ResponseInterface
    {
        try {
            $user = new User($req->data['id'], new Group($req->data['user_group']));
            $this->service->insertUser($user);
            return new CreatedResponse('user created', $req->data);
        } catch(GraphServiceException $e) {
            throw $e;
        }
        
        return new OKResponse('user created', $req->data);
    }

    public function updateUser(Request $req): ResponseInterface
    {
        try {
            $user = new User($req->data['id'], new Group($req->data['user_group']));
            $this->service->updateUser($user);
            return new OKResponse('user updated', $req->data);
        } catch(GraphServiceException $e) {
            throw $e;
        }
    }

    public function getGraph(Request $req): ResponseInterface
    {
        try {
            $data = $this->service->getGraph()->toArray();
            return new OKResponse('get graph', $data);
        } catch (Exception $e) {
            throw $e;
        }

        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getNode(Request $req): ResponseInterface
    {
        try {
            $id = $req->getParam('id');
            $node = $this->service->getNode($id);
            if(is_null($node)) {
                return new NotFoundResponse('node not found', ['id' => $id]);
            }
            $data = $node->toArray();
            return new OKResponse('node found', $data);
        } catch( Exception $e)
        {
            throw $e;
        }

        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getNodes(Request $req): ResponseInterface
    {
        try {
            $nodes = $this->service->getNodes();
            // TODO: corrigir
            return new OKResponse('nodes found', []);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function insertNode(Request $req): ResponseInterface
    {
        $this->logger->debug('inserting node', $req->toArray());
        try {
            $data = json_decode($req->data['data'], true);
            $node = new Node($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $data);
            $this->service->insertNode($node);
            $this->logger->info('node inserted', $req->data);
            return new CreatedResponse('node inserted', $req->data);
        } catch( GraphServiceException $e)
        {
            throw $e;
        }
        return new InternalServerErrorResponse('unknow error inserting node', $req->data);
    }
    
    public function updateNode(Request $req): ResponseInterface
    {
        $this->logger->debug('updating node', $req->data);

        try {
            $data = json_decode($req->data['data'], true);
            $node = new Node($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $data);
            $this->service->updateNode($node);
            $this->logger->info('node updated', $req->data);

            $req->data['data'] = $data;
            $resp = new CreatedResponse('node updated', $req->data);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse('unknow todo in updateNode', $req->data);
    }
    
    public function deleteNode(Request $req): ResponseInterface
    {
        try {
            $this->service->deleteEdge($req->data['id']);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse('unknow todo in deleteNode', $req->data);
    }

    public function getEdge(Request $req): ResponseInterface
    {
        try {
            $edge = $this->service->getEdge($req->data['source'], $req->data['target']);
            $data = $edge->toArray();
            return new OKResponse('node found', $data);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function getEdges(Request $req): ResponseInterface
    {
        try {
            $edges = $this->service->getEdges();
            // TODO: corrigir
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function insertEdge(Request $req): ResponseInterface
    {
        try {
            $edge = new Edge(null, $req->data['source'], $req->data['target']);
            $this->service->insertEdge($edge);
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function updateEdge(Request $req): ResponseInterface
    {
        try {
            $edge = new Edge($req->data['id'], $req->data['source'], $req->data['target'], $req->data['data']);
            $this->service->updateEdge($edge);
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function deleteEdge(Request $req): ResponseInterface
    {
        try {
            $id = $req->data['id'];
            $this->service->deleteEdge($id);
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getStatuses(Request $req): ResponseInterface
    {
        try {
            $statuses = $this->service->getStatuses();
            return new OKResponse('node found', []);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function getNodeStatus(Request $req): ResponseInterface
    {
        try {
            $status = $this->service->getNodeStatus($req->data['id']);
            $data = $status->toArray();
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function updateNodeStatus(Request $req): ResponseInterface
    {
        try {
            $status = new NodeStatus($req->data['node_id'], $req->data['status']);
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getLogs(Request $req): ResponseInterface
    {
        try {
            $logs = $this->service->getLogs($req->getParam('limit'));
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
}

final class RequestRouter
{
    private $routes = [
        ['method' => 'GET',    'path' => '/getGraph',      'class_method' => 'getGraph'],
        ['method' => 'GET',    'path' => '/getNode',       'class_method' => 'getNode'],
        ['method' => 'GET',    'path' => '/getNodes',      'class_method' => 'getNodes'],
        ['method' => 'POST',   'path' => '/insertNode',    'class_method' => 'insertNode'],
        ['method' => 'UPDATE', 'path' => '/updateNode',    'class_method' => 'updateNode'],
        ['method' => 'DELETE', 'path' => '/deleteNode',    'class_method' => 'deleteNode'],
        ['method' => 'GET',    'path' => '/getEdge',       'class_method' => 'getEdge'],
        ['method' => 'GET',    'path' => '/getEdges',      'class_method' => 'getEdges'],
        ['method' => 'POST',   'path' => '/insertEdge',    'class_method' => 'insertEdge'],
        ['method' => 'UPDATE', 'path' => '/updateEdge',    'class_method' => 'updateEdge'],
        ['method' => 'DELETE', 'path' => '/deleteEdge',    'class_method' => 'deleteEdge'],
        ['method' => 'GET',    'path' => '/getStatuses',   'class_method' => 'getStatuses'],
        ['method' => 'GET',    'path' => '/getNodeStatus', 'class_method' => 'getNodeStatus'],
        ['method' => 'GET',    'path' => '/getLogs',       'class_method' => 'getLogs'],
    ];

    public GraphController $controller;
    
    public function __construct(GraphController $controller)
    {
        $this->controller = $controller;
    }

    public function handle(): void
    {
        $method     = $_SERVER['REQUEST_METHOD'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        
        $path = str_replace($scriptName, '', $requestUri);
        
        $req = new Request();

        foreach($this->routes as $route)
        {
            if ($route['method'] == $method && $route['path'] == $path)
            {
                $method = $route['class_method'];
                $resp = $this->controller->$method($req);
                print_r($resp);
                exit();
            }
        }
    }
}


