<?php

declare(strict_types=1);

final class User
{
    public string $id;
    public ?string $ipAddress;
    public Group $group;

    public function __construct(string $id, ?string $ipAddress, Group $group)
    {
        $this->id = $id;
        $this->ipAddress = $ipAddress;
        $this->group = $group;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'group' => [
                'id' => $this->group->id,
            ],
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
    private const ALLOWED_CATEGORIES  = ['business', 'application', 'infrastructure'];
    private const ALLOWED_TYPES       = ['server', 'database', 'application', 'network'];
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

final class GraphContext
{
    public static User $user;
}

interface GraphDatabaseInterface
{
    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): bool;
    public function updateUser(string $id, string $group): bool;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdgeById(string $id): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, array $data = []): bool;
    public function updateEdge(string $id, string $source, string $target, array $data = []): bool;
    public function deleteEdge(string $id): bool;

    public function getStatuses(): array;
    public function getNodeStatus(string $id): ?string;
    public function setNodeStatus(string $id, string $status): void;

    public function getLogs($limit): array;
    public function insertAuditLog(
        string $entity_type, 
        string $entity_id, 
        string $action, 
        ?array $old_data = null, 
        ?array $new_data = null,
        string $user_id,
        string $ip_address): bool;
}

final class GraphDatabase implements GraphDatabaseInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initSchema();
    }

    public function getUser(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            if ($row) {
                return $row;
            }
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch node failed: " . $e->getMessage());
        }
        return null;
    }

    public function insertUser(string $id, string $group): bool
    {
        try {
            $sql = "
                INSERT OR IGNORE INTO users 
                (id, user_group)
                VALUES (:id, :group)";
            $stmt             = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id'       => $id,
                ':group'    => $group
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase insert node failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser(string $id, string $group): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET    user_group = :group
                WHERE  id = :id"
            );
            
            $stmt->execute([
                ':id'       => $id,
                ':group'    => $group
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update node: " . $e->getMessage());
        }
    }

    public function getNode(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            if ($row) {
                return $row;
            }
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch node failed: " . $e->getMessage());
        }
        return null;
    }

    public function getNodes(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM nodes");
            $rows = $stmt->fetchAll();
            return $rows;
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all nodes failed: " . $e->getMessage());
            return [];
        }
    }

    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        try {
            $sql = "
                INSERT OR IGNORE INTO nodes 
                (id, label, category, type, data) 
                VALUES (:id, :label, :category, :type, :data)";
            $stmt             = $this->pdo->prepare($sql);
            $data['id']       = $id;
            $data['label']    = $label;
            $data['category'] = $category;
            $data['type']     = $type;
            $stmt->execute([
                ':id'       => $id,
                ':label'    => $label,
                ':category' => $category,
                ':type'     => $type,
                ':data'     => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase insert node failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE nodes
                SET    label = :label, 
                       category = :category, 
                       type = :type, 
                       data = :data
                WHERE  id = :id");
            $data['id']       = $id;
            $data['label']    = $label;
            $data['category'] = $category;
            $data['type']     = $type;

            $stmt->execute([
                ':id'       => $id,
                ':label'    => $label,
                ':category' => $category,
                ':type'     => $type,
                ':data'     => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update node: " . $e->getMessage());
        }
    }

    public function deleteNode(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase delete node failed: " . $e->getMessage());
            return false;
        }
    }

    public function getEdge(string $source, string $target): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM edges
                WHERE source = :source AND target = :target
            ");
            $stmt->execute([
                ':source' => $source,
                ':target' => $target
            ]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        } catch (PDOException $e) {
            error_log("GraphDatabase edge exists check failed: " . $e->getMessage());
        }
        return null;
    }

    public function getEdgeById(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM edges
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
            ]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        } catch (PDOException $e) {
            error_log("GraphDatabase edge exists check failed: " . $e->getMessage());
        }
        return null;
    }

    public function getEdges(): array
    {
        try {
            $stmt  = $this->pdo->query("SELECT * FROM edges");
            $rows  = $stmt->fetchAll();
            return $rows;
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all edges failed: " . $e->getMessage());
            return [];
        }
    }

    public function insertEdge(string $id, string $source, string $target, array $data = []): bool
    {
        // verify if the inverse edge exists
        $r = $this->getEdge($target, $source);
        if ($r) {
            return false;
        }

        try {
            $sql = "
                INSERT OR IGNORE INTO edges
                (id, source, target, data)
                VALUES (:id, :source, :target, :data)";
            $stmt           = $this->pdo->prepare($sql);
            $data['id']     = $id;
            $data['source'] = $source;
            $data['target'] = $target;
            $stmt->execute([
                ':id'     => $id,
                ':source' => $source,
                ':target' => $target,
                ':data'   => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase insert edge or ignore failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateEdge(string $id, string $source, string $target, array $data = []): bool
    {
        try {
            $data['id']     = $id;
            $data['source'] = $source;
            $data['target'] = $target;

            $stmt = $this->pdo->prepare("
                UPDATE edges
                SET data = :data
                WHERE id = :id");
            $stmt->execute([
                ':id'   => $id,
                ':data' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update node: " . $e->getMessage());
        }
    }

    public function deleteEdge(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM edges WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase delete edge failed: " . $e->getMessage());
            return false;
        }
    }

    public function getStatuses(): array
    {
        $stmt   = $this->pdo->query("
            SELECT n.id,
                   s.status
            FROM   nodes n
            LEFT JOIN status s ON n.id = s.node_id");
        $status = $stmt->fetchAll();
        return $status;
    }

    public function getNodeStatus(string $id): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT s.status
            FROM nodes n
            LEFT JOIN status s
            ON n.id = s.node_id
            WHERE n.id = ?");
        $stmt->execute([$id]);
        $status = $stmt->fetch();
        return $status ? $status['status'] : null;
    }

    public function setNodeStatus(string $id, string $status): void
    {
        $stmt = $this->pdo->prepare("REPLACE INTO status (node_id, status) VALUES (?, ?)");
        $stmt->execute([$id, $status]);
    }

    public function getLogs($limit): array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM audit
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $rows;
    }

    public function insertAuditLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address)
                VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)
            ");

            $stmt->execute([
                ':entity_type' => $entity_type,
                ':entity_id'   => $entity_id,
                ':action'      => $action,
                ':old_data'    => $old_data !== null ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null,
                ':new_data'    => $new_data !== null ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null,
                ':user_id'     => $user_id,
                ':ip_address'  => $ip_address
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("GraphDatabase audit log insert failed: " . $e->getMessage());
            return false;
        }
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                user_group TEXT NOT NULL
            )
        ");

        $this->pdo->exec("INSERT INTO users VALUES('admin', 'admin')");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS nodes (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL,
                category TEXT NOT NULL,
                type TEXT NOT NULL,
                data TEXT NOT NULL
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS edges (
                id TEXT PRIMARY KEY,
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                data TEXT,
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            )
        ");

        $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target)");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS status (
                node_id TEXT PRIMARY KEY NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            )
        ");

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
            )
        ");
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

interface GraphServiceInterface
{
    public function getUser(string $id): ?User;
    public function insertUser(User $user): bool;
    public function updateUser(User $user): bool;

    public function getGraph(): Graph;

    public function getNode(string $id): ?Node;
    public function getNodes(): Nodes;
    public function insertNode(Node $node): bool;
    public function updateNode(Node $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): Edges;
    public function insertEdge(Edge $edge): bool;
    public function updateEdge(Edge $edge): bool;
    public function deleteEdge(string $id): bool;

    public function getStatuses(): NodeStatuses;
    public function getNodeStatus(string $id): NodeStatus;
    public function setNodeStatus(NodeStatus $status): void;

    public function getLogs($limit): AuditLogs;
}

final class GraphService implements GraphServiceInterface
{
    private const SECURE_ACTIONS = [
        'GraphService::getGraph'      => true,
        'GraphService::getNode'       => true,
        'GraphService::getNodes'      => true,
        'GraphService::getEdge'       => true,
        'GraphService::getEdges'      => true,
        'GraphService::getStatuses'   => true,
        'GraphService::getNodeStatus' => true,
        'GraphService::setNodeStatus' => true,
        'GraphService::getLogs'       => true,
        'GraphService::insertNode'    => false,
        'GraphService::updateNode'    => false,
        'GraphService::deleteNode'    => false,
        'GraphService::insertEdge'    => false,
        'GraphService::updateEdge'    => false,
        'GraphService::deleteEdge'    => false,

        'GraphService::insertAuditLog' => false,
    ];

    private GraphDatabaseInterface $db;

    public function __construct(GraphDatabaseInterface $db)
    {
        $this->db = $db;
    }

    public function getUser(string $id): ?User
    {
        $data = $this->db->getUser($id);
        if ($data) {
            $user = new User($id, null, $data['user_group']);
            return $user;
        }
        return null;
    }

    public function insertUser(User $user): bool
    {
        return $this->db->insertUser($user->id, $user->group->id);
    }

    public function updateUser(User $user): bool
    {
        return $this->db->updateUser($user->id, $user->group->id);
    }

    public function getGraph(): Graph
    {
        $nodes = $this->getNodes()->nodes;
        $edges = $this->getEdges()->edges;
        return new Graph($nodes, $edges);
    }

    public function getNode(string $id): ?Node
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get node.");
        }

        $data = $this->db->getNode($id);
        if ($data) {
            return new Node(
                $data['id'],
                $data['label'],
                $data['category'],
                $data['type'],
                json_decode($data['data'], true)
            );
        }
        return null;
    }

    public function getNodes(): Nodes
    {
        if(! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get nodes.");
        }

        $nodesData = $this->db->getNodes();
        $nodes     = new Nodes();
        foreach ($nodesData as $data) {
            $node = new Node(
                $data['id'],
                $data['label'],
                $data['category'],
                $data['type'],
                json_decode($data['data'], true)
            );
            $nodes->addNode($node);
        }
        return $nodes;
    }

    public function insertNode(Node $node): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to insert node.");
        }

        $this->insertAuditLog(new AuditLog( 'node', $node->id, 'insert', null, $node->toArray()));
        return $this->db->insertNode(
            $node->id,
            $node->label,
            $node->category,
            $node->type,
            $node->data
        );
    }

    public function updateNode(Node $node): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to update node.");
        }

        $old = $this->getNode($node->id);
        $this->insertAuditLog(new AuditLog( 'node', $node->id, 'update', $old->toArray(), $node->toArray()));

        return $this->db->updateNode(
            $node->id,
            $node->label,
            $node->category,
            $node->type,
            $node->data
        );
    }

    public function deleteNode(string $id): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to delete node.");
        }

        $old = $this->getNode($id);
        $this->insertAuditLog(new AuditLog( 'node', $id, 'delete', $old->toArray(), null));
        return $this->db->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get edge.");
        }

        $edgesData = $this->db->getEdges();
        foreach ($edgesData as $data) {
            if ($data['source'] === $source && $data['target'] === $target) {
                return new Edge(
                    $data['id'],
                    $data['source'],
                    $data['target'],
                    json_decode($data['data'], true)
                );
            }
        }
        return null;
    }

    public function getEdges(): Edges
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get edges.");
        }
        $edgesData = $this->db->getEdges();
        $edges     = new Edges();
        foreach ($edgesData as $data) {
            $edge = new Edge(
                $data['id'],
                $data['source'],
                $data['target'],
                json_decode($data['data'], true)
            );
            $edges->addEdge($edge);
        }
        return $edges;
    }

    public function insertEdge(Edge $edge): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to insert edge.");
        }
        $this->insertAuditLog(new AuditLog( 'edge', $edge->id, 'insert', null, $edge->toArray()));
        return $this->db->insertEdge(
            $edge->id,
            $edge->source,
            $edge->target,
            $edge->data
        );
    }

    public function updateEdge(Edge $edge): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to update edge.");
        }
        $old = $this->getEdge($edge->source, $edge->target);
        $this->insertAuditLog(new AuditLog( 'edge', $edge->id, 'update', $old->toArray(), $edge->toArray()));

        return $this->db->updateEdge(
            $edge->id,
            $edge->source,
            $edge->target,
            $edge->data
        );
    }

    public function deleteEdge(string $id): bool
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to delete edge.");
        }
        return $this->db->deleteEdge($id);
    }

    public function getStatuses(): NodeStatuses
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get statuses.");
        }

        $statusesData = $this->db->getStatuses();
        $nodeStatuses = new NodeStatuses();
        foreach ($statusesData as $data) {
            $status = new NodeStatus(
                $data['id'],
                $data['status'] ?? 'unknown'
            );
            $nodeStatuses->addStatus($status);
        }
        return $nodeStatuses;
    }

    public function getNodeStatus(string $id): NodeStatus
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get node status.");
        }

        $statusData = $this->db->getNodeStatus($id);
        return new NodeStatus(
            $id,
            $statusData ?? 'unknown'
        );
    }

    public function setNodeStatus(NodeStatus $status): void
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to set node status.");
        }

        $this->db->setNodeStatus($status->nodeId, $status->status);
    }

    public function getLogs($limit): AuditLogs
    {
        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to get audit logs.");
        }

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
    }

    public function insertAuditLog(AuditLog $auditLog): void
    {
        $user_id   = GraphContext::$user->id;
        $ip_address = GraphContext::$user->ipAddress;

        if (! $this->isAllowed(__METHOD__)) {
            throw new RuntimeException("Permission denied to insert audit log.");
        }
        
        $this->db->insertAuditLog(
            $auditLog->entityType,
            $auditLog->entityId,
            $auditLog->action,
            $auditLog->oldData,
            $auditLog->newData,
            $user_id,
            $ip_address
        );
    }

    private function isAllowed(string $action): bool
    {
        $group = GraphContext::$user->group;

        // validate action
        // if action is one of the keys in the array self::SECURE_ACTIONS
        if (!array_key_exists($action, self::SECURE_ACTIONS)) {
            throw new RuntimeException("Action not defined in secure actions. Action: {$action}");
        }

        // if is admin, allow all
        if ($group->id === 'admin') {
            return true;
        }

        // if action is in the SAFE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            return true;
        }
        
        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] == false && $group->id == 'contributor')
        {
            return true;
        }

        return false;
    }
}

final class Request
{
    public array $data;

    public function getParam($name): string
    {
        if(isset($_GET[$name])) {
            return $_GET[$name];
        }

        throw new RuntimeException("param '{$name}' not found");
    }

    public function getPath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        $path = str_replace($scriptName, '', $requestUri);
        return $path;
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
    public function setNodeStatus(Request $req): ResponseInterface;

    public function getLogs(Request $req): ResponseInterface;
}

final class GraphController implements GraphControllerInterface
{
    private GraphServiceInterface $service;

    public function __construct(GraphServiceInterface $service)
    {
        $this->service = $service;
    }

    public function getUser(Request $req): ResponseInterface
    {
        $data = $this->service->getUser($req->data['id']);
        $user = new User($data['id'], null, new Group($data['user_group']));
        return new OKResponse('user found', $user->toArray());
    }

    public function insertUser(Request $req): ResponseInterface
    {
        $user = new User($req->data['id'], null, $req->data['user_group']);
        $this->service->insertUser($user);
        return new OKResponse('user created', $req->data);
    }

    public function updateUser(Request $req): ResponseInterface
    {
        $user = new User($req->data['id'], null, $req->data['user_group']);
        $this->service->updateUser($user);
        return new CreatedResponse('user updated', $req->data);
    }

    public function getGraph(Request $req): ResponseInterface
    {
        try {
            $data = $this->service->getGraph()->toArray();
            return new OKResponse('get graph', $data);
            return $resp;
        } catch (Exception $e) {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }

        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getNode(Request $req): ResponseInterface
    {
        try {
            $id = $req->getParam('id');
            $node = $this->service->getNode($id);
            $data = $node->toArray();
            return new OKResponse('node found', $data);
        } catch( Exception $e)
        {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function insertNode(Request $req): ResponseInterface
    {
        try {
            $node = new Node($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $req->data['data']);
            $this->service->insertNode($node);
        } catch( Exception $e)
        {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }
        
        return new InternalServerErrorResponse('unknow todo in insertNode', $req->data);
    }
    
    public function updateNode(Request $req): ResponseInterface
    {
        try {
            $edge = new Edge($req->data['id'], $req->data['source'], $req->data['target'], $req->data['data']);
            $this->service->insertEdge($edge);
        } catch( Exception $e)
        {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }
        
        return new InternalServerErrorResponse('unknow todo in updateNode', $req->data);
    }
    
    public function deleteNode(Request $req): ResponseInterface
    {
        try {
            $this->service->deleteEdge($req->data['id']);
        } catch( Exception $e)
        {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function setNodeStatus(Request $req): ResponseInterface
    {
        try {
            $status = new NodeStatus($req->data['node_id'], $req->data['status']);
            return new OKResponse('node found', []);
        } catch( Exception $e)
        {
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
            return new InternalServerErrorResponse($e->getMessage(), $req->data);
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
}

final class RequestRouter
{
    private $routes = [
        ['method' => 'GET', 'path' => '/getGraph', 'class_method' => 'getGraph'],
        
        ['method' => 'GET', 'path' => '/getNode', 'class_method' => 'getNode'],
        ['method' => 'GET', 'path' => '/getNodes', 'class_method' => 'getNodes'],
        ['method' => 'GET', 'path' => '/insertNode', 'class_method' => 'insertNode'],
        ['method' => 'GET', 'path' => '/updateNode', 'class_method' => 'updateNode'],
        ['method' => 'GET', 'path' => '/deleteNode', 'class_method' => 'deleteNode'],

        ['method' => 'GET', 'path' => '/getEdge', 'class_method' => 'getEdge'],
        ['method' => 'GET', 'path' => '/getEdges', 'class_method' => 'getEdges'],
        ['method' => 'GET', 'path' => '/insertEdge', 'class_method' => 'insertEdge'],
        ['method' => 'GET', 'path' => '/updateEdge', 'class_method' => 'updateEdge'],
        ['method' => 'GET', 'path' => '/deleteEdge', 'class_method' => 'deleteEdge'],

        ['method' => 'GET', 'path' => '/getStatuses', 'class_method' => 'getStatuses'],
        ['method' => 'GET', 'path' => '/getNodeStatus', 'class_method' => 'getNodeStatus'],

        ['method' => 'GET', 'path' => '/getLogs', 'class_method' => 'getLogs'],

        ];

    public GraphController $controller;
    
    public function __construct(GraphController $controller)
    {
        $this->controller = $controller;
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

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

function tests() {
    GraphContext::$user = new User('test_user', '127.0.0.1', new Group('contributor'));

    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $graphDb = new GraphDatabase($pdo);
    $graphService = new GraphService($graphDb);

    $graphController = new GraphController($graphService);

    $insertNodeReq = new Request();
    $insertNodeReq->data = ['id' => 'node1', 'label' => 'node1', 'category' => 'business', 'type' => 'server', 'data' => ['info' => 'first node']];
    $resp = $graphController->insertNode($insertNodeReq);
    print_r($resp);
    exit();

    
    // $node1 = new Node('node1', 'Node 1', 'business', 'application', ['info' => 'First node']);
    // $node2 = new Node('node2', 'Node 2', 'infrastructure', 'server', ['info' => 'Second node']);
    // $edge = new Edge('edge1', 'node1', 'node2', ['relation' => 'connects to']);
    
    // $graphService->insertNode($node1);
    // $graphService->insertNode($node2);
    // $graphService->insertEdge($edge);

    // $graphService->deleteEdge('node1', 'node2');
    // $graphService->deleteNode('node1');
    // $graphService->deleteNode('node2');

    // $auditLogs = $graphService->getLogs(10);
    // print_r($auditLogs);
    // exit();
    // foreach ($auditLogs->logs as $log) {
    //     echo "Audit Log - Entity: " . $log->entityType . ", Action:
    // " . $log->action . ", Entity ID: " . $log->entityId . PHP_EOL;
    // }

    // $statuses = $graphService->getStatuses();
    // foreach ($statuses->statuses as $status) {
    //     echo "Node ID: " . $status->nodeId . ", Status: " . $status->status . PHP_EOL;
    // }
    
    // $nodeStatus = $graphService->getNodeStatus('node1');
    // echo "Node1 Status: " . $nodeStatus->status . PHP_EOL;

    // $status = $graphService->getNodeStatus('node1');
    //echo "Node1 Status: " . $status->status . PHP_EOL;
}

tests();