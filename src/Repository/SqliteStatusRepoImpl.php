<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

use PDO;

final class SqliteStatusRepoImpl implements StatusRepoInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initSchema();
    }

    public function getStatuses(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM status order by node_id ASC");
        $status = $stmt->fetchAll();
        return $status;
    }

    public function getNodeStatus(string $id): string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM status WHERE node_id = ?");
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return 'unknown';
        }
        return $status;
    }

    public function setNodeStatus(string $id, string $status): void
    {
        // validate if status is one of the allowed values
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }
        $stmt = $this->pdo->prepare("REPLACE INTO status (node_id, status) VALUES (?, ?)");
        $stmt->execute([$id, $status]);
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS status (
                node_id TEXT PRIMARY KEY NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)");
    }
}
