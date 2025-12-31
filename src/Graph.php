<?php

declare(strict_types=1);

namespace Opsminded\Graph;

use RuntimeException;
use Exception;

class Graph
{
    private Database $database;

    private const ALLOWED_CATEGORIES = ['business', 'application', 'infrastructure'];
    private const ALLOWED_TYPES = ['server', 'database', 'application', 'network'];
    private const ALLOWED_STATUSES = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

    public function __construct(string $db_file)
    {
        $this->database = new Database($db_file);
    }

    public function get(): array
    {
        $nodesData = $this->database->nodes();
        $edgesData = $this->database->edges();

        $nodes = [];
        foreach ($nodesData as $row) {
            $nodes[] = [
                'data' => json_decode($row['data'], true)
            ];
        }

        $edges = [];
        foreach ($edgesData as $row) {
            $edges[] = [
                'source' => $row['source'],
                'target' => $row['target']
            ];
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    public function nodeExists(string $id): bool
    {
        return $this->database->nodeExists($id);
    }

    /**
     * Validate node data to ensure category and type are present and valid
     *
     * @throws RuntimeException if validation fails
     */
    private function validateNodeData(array $data, bool $requireAll = true): void
    {
        // Check category
        if ($requireAll && !isset($data['category'])) {
            throw new RuntimeException('Node category is required');
        }
        if (isset($data['category']) && !in_array($data['category'], self::ALLOWED_CATEGORIES, true)) {
            throw new RuntimeException(
                'Invalid category. Allowed values: ' . implode(', ', self::ALLOWED_CATEGORIES)
            );
        }

        // Check type
        if ($requireAll && !isset($data['type'])) {
            throw new RuntimeException('Node type is required');
        }
        if (isset($data['type']) && !in_array($data['type'], self::ALLOWED_TYPES, true)) {
            throw new RuntimeException(
                'Invalid type. Allowed values: ' . implode(', ', self::ALLOWED_TYPES)
            );
        }
    }

    public function addNode(string $id, array $data): bool
    {
        if ($this->database->nodeExists($id)) {
            return true;
        }

        // Validate that category and type are present and valid
        $this->validateNodeData($data, true);

        $data['id'] = $id;
        $result     = $this->database->insertNode($id, $data);

        if ($result) {
            $this->auditLog('node', $id, 'create', null, $data);
        }

        return $result;
    }

    public function updateNode(string $id, array $data): bool
    {
        $old_data = $this->database->getNode($id);

        // Validate category and type if they're being updated (not required for updates)
        $this->validateNodeData($data, false);

        $data['id'] = $id;
        $rowCount   = $this->database->updateNode($id, $data);

        if ($rowCount > 0) {
            $this->auditLog('node', $id, 'update', $old_data, $data);
            return true;
        }

        return false;
    }

    public function removeNode(string $id): bool
    {
        try {
            $this->database->beginTransaction();

            // Fetch node data before deleting
            $old_data = $this->database->getNode($id);
            if (!$old_data) {
                $this->database->rollBack();
                return false;
            }

            // Delete the node
            [$rowCount, $_] = $this->database->deleteNode($id);

            if ($rowCount > 0) {
                $this->auditLog('node', $id, 'delete', $old_data, null);
            }

            $this->database->commit();
            return $rowCount > 0;
            // @codeCoverageIgnoreStart
        } catch (Exception $e) {
            $this->database->rollBack();
            error_log("Graph remove node failed: " . $e->getMessage());
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    public function edgeExists(string $source, string $target): bool
    {
        return $this->database->edgeExists($source, $target);
    }

    public function addEdge(string $source, string $target): bool
    {
        $result = $this->database->insertEdge($source, $target);

        if ($result) {
            $this->auditLog('edge', $source . '->' . $target, 'create', null, null);
        }

        return $result;
    }

    public function removeEdge(string $source, string $target): bool
    {
        [$rowCount, $old_data] = $this->database->deleteEdge($source, $target);

        if ($rowCount > 0) {
            $this->auditLog('edge', $source . '->' . $target, 'delete', null, null);
            return true;
        }

        return false;
    }

    public function auditLog(
        string $entity_type,
        string $entity_id,
        string $action,
        ?array $old_data = null,
        ?array $new_data = null,
        ?string $user_id = null,
        ?string $ip_address = null
    ): bool {
        // Use global audit context if user_id/ip_address not provided
        if ($user_id === null) {
            $user_id = AuditContext::getUser();
        }
        if ($ip_address === null) {
            $ip_address = AuditContext::getIp();
        }

        return $this->database->insertAuditLog(
            $entity_type,
            $entity_id,
            $action,
            $old_data,
            $new_data,
            $user_id,
            $ip_address
        );
    }

    public function getAuditHistory(?string $entity_type = null, ?string $entity_id = null): array
    {
        return $this->database->getAuditHistory($entity_type, $entity_id);
    }

    public function setNodeStatus(string $node_id, string $status): bool
    {
        if (!$this->database->nodeExists($node_id)) {
            return false;
        }

        // Validate status value
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new RuntimeException(
                'Invalid status. Allowed values: ' . implode(', ', self::ALLOWED_STATUSES)
            );
        }

        $result = $this->database->insertNodeStatus($node_id, $status);

        return $result;
    }

    public function getNodeStatus(string $node_id): ?NodeStatus
    {
        $row = $this->database->getLatestNodeStatus($node_id);

        if (!$row) {
            return null;
        }

        return new NodeStatus($row['node_id'], $row['status'], $row['created_at']);
    }

    public function getNodeStatusHistory(string $node_id): array
    {
        $rows = $this->database->getNodeStatusHistory($node_id);

        $statuses = [];
        foreach ($rows as $row) {
            $statuses[] = new NodeStatus($row['node_id'], $row['status'], $row['created_at']);
        }

        return $statuses;
    }

    public function status(): array
    {
        $rows = $this->database->getAllLatestStatuses();

        $statuses = [];
        foreach ($rows as $row) {
            $statuses[] = new NodeStatus($row['node_id'], $row['status'], $row['created_at']);
        }

        return $statuses;
    }
}
