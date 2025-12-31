<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

use PDO;
use PDOException;
use Opsminded\Graph\AuditContext;

final class AuditRepoImpl implements GraphRepoInterface
{
    private PDO $pdo;
    private GraphRepoInterface $repo;

    public function __construct(PDO $pdo, GraphRepoInterface $repo)
    {
        $this->pdo = $pdo;
        $this->repo = $repo;
        $this->initSchema();
    }

    public function getNode(string $id): ?array
    {
        $this->insertAuditLog('node', $id, 'get_node');
        return $this->repo->getNode($id);
    }

    public function getNodes(): array
    {
        $this->insertAuditLog('node', 'all', 'get_nodes');
        return $this->repo->getNodes();
    }

    public function getNodeExists(string $id): bool
    {
        $this->insertAuditLog('node', $id, 'get_node_exists');
        return $this->repo->getNodeExists($id);
    }

    public function insertNode(string $id, array $data): bool
    {
        $this->insertAuditLog('node', $id, 'insert_node', null, $data);
        return $this->repo->insertNode($id, $data);
    }

    public function updateNode(string $id, array $data): bool
    {
        $oldData = $this->repo->getNode($id);
        $this->insertAuditLog('node', $id, 'update_node', $oldData, $data);
        return $this->repo->updateNode($id, $data);
    }

    public function deleteNode(string $id): bool
    {
        $oldData = $this->repo->getNode($id);
        $this->insertAuditLog('node', $id, 'delete_node', $oldData, null);
        return $this->repo->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?array
    {
        $this->insertAuditLog('edge', $source . '->' . $target, 'get_edge');
        return $this->repo->getEdge($source, $target);
    }

    public function getEdges(): array
    {
        $this->insertAuditLog('edge', 'all', 'get_edges');
        return $this->repo->getEdges();
    }

    public function getEdgeExists(string $source, string $target): bool
    {
        $this->insertAuditLog('edge', $source . '->' . $target, 'get_edge_exists');
        return $this->repo->getEdgeExists($source, $target);
    }

    public function insertEdge(string $source, string $target, array $data = []): bool
    {
        $this->insertAuditLog('edge', $source . '->' . $target, 'insert_edge', null, $data);
        return $this->repo->insertEdge($source, $target, $data);
    }

    public function updateEdge(string $source, string $target, array $data = []): bool
    {
        $oldData = $this->repo->getEdge($source, $target);
        $this->insertAuditLog('edge', $source . '->' . $target, 'update_edge', $oldData, $data);
        return $this->repo->updateEdge($source, $target, $data);
    }

    public function deleteEdge(string $source, string $target): bool
    {
        $oldData = $this->repo->getEdge($source, $target);
        $this->insertAuditLog('edge', $source . '->' . $target, 'delete_edge', $oldData, null);
        return $this->repo->deleteEdge($source, $target);
    }

    public function insertAuditLog(
        string $entity_type,
        string $entity_id,
        string $action,
        ?array $old_data = null,
        ?array $new_data = null,
    ): bool {
        try {

            $user_id = AuditContext::getUser();
            $ip_address = AuditContext::getIp();

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
            // @codeCoverageIgnoreStart
        } catch (PDOException $e) {
            error_log("GraphDatabase audit log insert failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS audit (
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

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_entity ON audit(entity_type, entity_id)");
        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_created ON audit(created_at)");
    }
}
