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
        $stmt = $this->pdo->query("SELECT * FROM status");
        $status = $stmt->fetchAll();
        return $status;
    }

    public function getNodeStatus(string $id): string
    {
        $stmt = $this->pdo->prepare("SELECT status FROM status WHERE node_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    public function setNodeStatus(string $id, string $status): void
    {
        $stmt = $this->pdo->prepare("REPLACE INTO status (node_id, status) VALUES (?, ?)");
        $stmt->execute([$id, $status]);
    }

    private function initSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS status (
                node_id TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)");
    }
}
