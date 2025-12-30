<?php

declare(strict_types=1);

namespace Opsminded\Graph;

use PDO;
use PDOException;
use RuntimeException;
use Exception;

class Database
{
    private string $db_file;
    private ?PDO $db = null;

    public function __construct(string $db_file)
    {
        $this->db_file = $db_file;

        $isNew = !file_exists($db_file) || filesize($db_file) === 0;

        if ($isNew) {
            $this->initSchema();
        }
    }

    private function initSchema(): void
    {
        $db = $this->getDb();

        $db->exec("
            CREATE TABLE IF NOT EXISTS nodes (
                id TEXT PRIMARY KEY,
                data TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS edges (
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (source, target),
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL,
                action TEXT NOT NULL,
                old_data TEXT,
                new_data TEXT,
                user_id TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit_log(entity_type, entity_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_audit_created ON audit_log(created_at)");

        $db->exec("
            CREATE TABLE IF NOT EXISTS node_status (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                node_id TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            )
        ");

        $db->exec("CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON node_status(node_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_node_status_created ON node_status(created_at)");
    }

    private function getDb(): PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }

        try {
            $this->db = new PDO('sqlite:' . $this->db_file);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Ensure SQLite enforces foreign key constraints (ON DELETE CASCADE)
            $this->db->exec('PRAGMA foreign_keys = ON');
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd

        return $this->db;
    }

    public function nodes(): array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->query("SELECT id, data FROM nodes ORDER BY created_at");
            return $stmt->fetchAll();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all nodes failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function nodeExists(string $id): bool
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("SELECT COUNT(*) FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase node exists check failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function getNode(string $id): ?array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("SELECT data FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return $row ? json_decode($row['data'], true) : null;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch node failed: " . $e->getMessage());
            return null;
        }
        // @codeCoverageIgnoreEnd
    }

    public function insertNode(string $id, array $data): bool
    {
        // Use INSERT OR IGNORE so calling insertNode on an existing id
        // will not error and will simply do nothing — return true.
        try {
            $db  = $this->getDb();
            $sql = "INSERT OR IGNORE INTO nodes (id, data) VALUES (:id, :data)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id'   => $id,
                ':data' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase insert node failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function updateNode(string $id, array $data): int
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("UPDATE nodes SET data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([
                ':id'   => $id,
                ':data' => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return $stmt->rowCount();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            return 0;
        }
        // @codeCoverageIgnoreEnd
    }

    

    public function deleteNode(string $id): array
    {
        try {
            $db = $this->getDb();

            $stmt = $db->prepare("SELECT data FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $old_row  = $stmt->fetch();
            $old_data = $old_row ? json_decode($old_row['data'], true) : null;

            $stmt = $db->prepare("DELETE FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return [$stmt->rowCount(), $old_data];
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase delete node failed: " . $e->getMessage());
            return [0, null];
        }
        // @codeCoverageIgnoreEnd
    }

    public function edges(): array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->query("SELECT source, target FROM edges ORDER BY created_at");
            return $stmt->fetchAll();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all edges failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function edgeExists(string $source, string $target): bool
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM edges
                WHERE (source = :source AND target = :target)
                   OR (source = :target AND target = :source)
            ");
            $stmt->execute([
                ':source' => $source,
                ':target' => $target
            ]);
            return $stmt->fetchColumn() > 0;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase edge exists check failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function insertEdge(string $source, string $target): bool
    {
        try {
            $db  = $this->getDb();
            $sql = "INSERT OR IGNORE INTO edges (source, target) VALUES (:source, :target)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':source' => $source,
                ':target' => $target,
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase insert edge or ignore failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function deleteEdge(string $source, string $target): array
    {
        try {
            $db = $this->getDb();

            $stmt = $db->prepare("SELECT source, target FROM edges WHERE source = :source AND target = :target");
            $stmt->execute([':source' => $source, ':target' => $target]);
            $old_row  = $stmt->fetch();
            $old_data = null;

            $stmt = $db->prepare("DELETE FROM edges WHERE source = :source AND target = :target");
            $stmt->execute([':source' => $source, ':target' => $target]);

            return [$stmt->rowCount(), $old_data];
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase delete edge failed: " . $e->getMessage());
            return [0, null];
        }
        // @codeCoverageIgnoreEnd
    }

    public function deleteEdgesFrom(string $source): array
    {
        try {
            $db = $this->getDb();

            // Fetch edges before deleting
            $stmt = $db->prepare("SELECT source, target FROM edges WHERE source = :source");
            $stmt->execute([':source' => $source]);
            $edges = $stmt->fetchAll();

            // Delete edges
            $stmt = $db->prepare("DELETE FROM edges WHERE source = :source");
            $stmt->execute([':source' => $source]);

            // Return edges
            $result = [];
            foreach ($edges as $edge) {
                $result[] = [
                    'source' => $edge['source'],
                    'target' => $edge['target']
                ];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("GraphDatabase delete edges from failed: " . $e->getMessage());
            return [];
        }
    }

    public function deleteEdgesByNode(string $nodeId): array
    {
        try {
            $db = $this->getDb();

            // Fetch edges before deleting
            $stmt = $db->prepare("SELECT source, target FROM edges WHERE source = :id OR target = :id");
            $stmt->execute([':id' => $nodeId]);
            $edges = $stmt->fetchAll();

            // Delete edges
            $stmt = $db->prepare("DELETE FROM edges WHERE source = :id OR target = :id");
            $stmt->execute([':id' => $nodeId]);

            // Return edges
            $result = [];
            foreach ($edges as $edge) {
                $result[] = [
                    'source' => $edge['source'],
                    'target' => $edge['target'],
                    
                ];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("GraphDatabase delete edges by node failed: " . $e->getMessage());
            return [];
        }
    }

    

    // Audit operations

    public function insertAuditLog(
        string $entity_type,
        string $entity_id,
        string $action,
        ?array $old_data = null,
        ?array $new_data = null,
        ?string $user_id = null,
        ?string $ip_address = null
    ): bool {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                INSERT INTO audit_log (entity_type, entity_id, action, old_data, new_data, user_id, ip_address)
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
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase audit log insert failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchAuditHistory(?string $entity_type = null, ?string $entity_id = null): array
    {
        try {
            $db = $this->getDb();

            $sql    = "SELECT * FROM audit_log WHERE 1=1";
            $params = [];

            if ($entity_type !== null) {
                $sql .= " AND entity_type = :entity_type";
                $params[':entity_type'] = $entity_type;
            }

            if ($entity_id !== null) {
                $sql .= " AND entity_id = :entity_id";
                $params[':entity_id'] = $entity_id;
            }

            $sql .= " ORDER BY created_at DESC, id DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();

            // Decode JSON data
            foreach ($logs as &$log) {
                $log['old_data'] = $log['old_data'] !== null ? json_decode($log['old_data'], true) : null;
                $log['new_data'] = $log['new_data'] !== null ? json_decode($log['new_data'], true) : null;
            }

            return $logs;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch audit history failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchAuditLogById(int $id, string $entity_type, string $entity_id): ?array
    {
        try {
            $db  = $this->getDb();
            $sql = "SELECT * FROM audit_log"
                . " WHERE id = :id"
                . " AND entity_type = :entity_type"
                . " AND entity_id = :entity_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id'          => $id,
                ':entity_type' => $entity_type,
                ':entity_id'   => $entity_id
            ]);
            $log = $stmt->fetch();

            if (!$log) {
                return null;
            }

            $log['old_data'] = $log['old_data'] !== null ? json_decode($log['old_data'], true) : null;
            $log['new_data'] = $log['new_data'] !== null ? json_decode($log['new_data'], true) : null;

            return $log;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch audit log by id failed: " . $e->getMessage());
            return null;
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchAuditLogsAfterTimestamp(string $timestamp): array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                SELECT * FROM audit_log
                WHERE created_at > :timestamp
                ORDER BY created_at DESC, id DESC
            ");
            $stmt->execute([':timestamp' => $timestamp]);
            $logs = $stmt->fetchAll();

            // Decode JSON data
            foreach ($logs as &$log) {
                $log['old_data'] = $log['old_data'] !== null ? json_decode($log['old_data'], true) : null;
                $log['new_data'] = $log['new_data'] !== null ? json_decode($log['new_data'], true) : null;
            }

            return $logs;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch audit logs after timestamp failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    // Status operations

    public function insertNodeStatus(string $nodeId, string $status): bool
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                INSERT INTO node_status (node_id, status)
                VALUES (:node_id, :status)
            ");
            $stmt->execute([
                ':node_id' => $nodeId,
                ':status'  => $status
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase insert node status failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchLatestNodeStatus(string $nodeId): ?array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                SELECT node_id, status, created_at
                FROM node_status
                WHERE node_id = :node_id
                AND created_at = (
                    SELECT MAX(created_at)
                    FROM node_status
                    WHERE node_id = :node_id
                )
                LIMIT 1
            ");
            $stmt->execute([':node_id' => $nodeId]);
            $row = $stmt->fetch();

            return $row ?: null;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch latest node status failed: " . $e->getMessage());
            return null;
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchNodeStatusHistory(string $nodeId): array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->prepare("
                SELECT node_id, status, created_at
                FROM node_status
                WHERE node_id = :node_id
                ORDER BY created_at DESC
            ");
            $stmt->execute([':node_id' => $nodeId]);
            return $stmt->fetchAll();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch node status history failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function fetchAllLatestStatuses(): array
    {
        try {
            $db   = $this->getDb();
            $stmt = $db->query("
                SELECT ns.node_id, ns.status, ns.created_at
                FROM node_status ns
                INNER JOIN (
                    SELECT node_id, MAX(created_at) as max_created_at
                    FROM node_status
                    GROUP BY node_id
                ) latest ON ns.node_id = latest.node_id AND ns.created_at = latest.max_created_at
                INNER JOIN nodes n ON ns.node_id = n.id
                ORDER BY ns.node_id
            ");
            return $stmt->fetchAll();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all latest statuses failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    // Transaction support

    public function beginTransaction(): bool
    {
        try {
            $db = $this->getDb();
            return $db->beginTransaction();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase begin transaction failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function commit(): bool
    {
        try {
            $db = $this->getDb();
            return $db->commit();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase commit failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function rollBack(): bool
    {
        try {
            $db = $this->getDb();
            return $db->rollBack();
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase rollback failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }
}
