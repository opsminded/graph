<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

use PDO;
use PDOException;
use RuntimeException;

final class SqliteGraphRepoImpl implements GraphRepoInterface
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initSchema();
    }



    public function getNode(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            if ($row) {
                return ['data' => json_decode($row['data'], true)];
            }

            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch node failed: " . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
        return null;
    }

    public function getNodes(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT data FROM nodes");
            $rows = $stmt->fetchAll();

            $nodes = [];
            foreach ($rows as $row) {
                $nodes[] = ['data' => json_decode($row['data'], true)];
            }
            return $nodes;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all nodes failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function getNodeExists(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetchColumn() > 0;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase node exists check failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }



    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        try {
            $sql        = "INSERT OR IGNORE INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)";
            $stmt       = $this->pdo->prepare($sql);
            $data['id'] = $id;
            $data['label'] = $label;
            $data['category'] = $category;
            $data['type'] = $type;
            $stmt->execute([
                ':id'      => $id,
                ':label'   => $label,
                ':category' => $category,
                ':type'    => $type,
                ':data'    => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase insert node failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE nodes SET label = :label, category = :category, type = :type, data = :data WHERE id = :id");
            $data['id'] = $id;
            $data['label'] = $label;
            $data['category'] = $category;
            $data['type'] = $type;
            
            $stmt->execute([
                ':id'      => $id,
                ':label'   => $label,
                ':category' => $category,
                ':type'    => $type,
                ':data'    => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return $stmt->rowCount() > 0;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update node: " . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }



    public function deleteNode(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM nodes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase delete node failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function getEdge(string $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM edges WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                return ['data' => json_decode($row['data'], true)];
            }
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch edge failed: " . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
        return null;
    }

    public function getEdges(): array
    {
        try {
            $stmt  = $this->pdo->query("SELECT data FROM edges");
            $rows  = $stmt->fetchAll();
            $edges = [];
            foreach ($rows as $row) {
                $edges[] = ['data' => json_decode($row['data'], true)];
            }
            return $edges;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase fetch all edges failed: " . $e->getMessage());
            return [];
        }
        // @codeCoverageIgnoreEnd
    }

    public function getEdgeExistsById(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM edges
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchColumn() > 0;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase edge exists check failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function getEdgeExistsByNodes(string $source, string $target): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM edges
                WHERE source = :source AND target = :target
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

    public function insertEdge(string $id, string $source, string $target, array $data = []): bool
    {
        // verify if the inverse edge exists
        $r = $this->getEdgeExistsByNodes($target, $source);
        if ($r) {
            return false;
        }
        
        try {
            $sql            = "INSERT OR IGNORE INTO edges (id, source, target, data) VALUES (:id, :source, :target, :data)";
            $stmt           = $this->pdo->prepare($sql);
            $data['id']     = $id;
            $data['source'] = $source;
            $data['target'] = $target;
            $stmt->execute([
                ':id' => $id,
                ':source' => $source,
                ':target' => $target,
                ':data'   => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase insert edge or ignore failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
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
                ':id' => $id,
                ':data'   => json_encode($data, JSON_UNESCAPED_UNICODE)
            ]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase update node failed: " . $e->getMessage());
            throw new RuntimeException("Failed to update node: " . $e->getMessage());
        }
        // @codeCoverageIgnoreEnd
    }

    public function deleteEdge(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM edges WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return true;
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase delete edge failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    private function initSchema(): void
    {
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
    }

        public static function createConnection(string $dbFile): PDO
    {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }
}
