<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Repository\SqliteStatusRepoImpl;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;

class SqliteStatusRepoImplTest extends TestCase
{
    private string $dbFilename;
    private ?PDO $pdo;
    private ?SqliteStatusRepoImpl $statusRepo;

    protected function setUp(): void
    {
        $this->dbFilename = sys_get_temp_dir() . '/statusdb' . uniqid() . '.sqlite';
        $this->pdo = SqliteGraphRepoImpl::createConnection($this->dbFilename);
        $this->statusRepo = new SqliteStatusRepoImpl($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->statusRepo = null;

        if (file_exists($this->dbFilename)) {
            @unlink($this->dbFilename);
        }
    }

    private function getStatusFromDb(string $nodeId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM status WHERE node_id = ?");
        $stmt->execute([$nodeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    private function getAllStatusesFromDb(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM status ORDER BY node_id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function testSchemaInitialization(): void
    {
        // Verify status table was created
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='status'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('status', $result['name']);
    }

    public function testSchemaHasCorrectColumns(): void
    {
        // Verify table structure
        $stmt = $this->pdo->query("PRAGMA table_info(status)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $columnNames = array_column($columns, 'name');
        $this->assertContains('node_id', $columnNames);
        $this->assertContains('status', $columnNames);
        $this->assertContains('created_at', $columnNames);
    }

    public function testSchemaHasCorrectIndexes(): void
    {
        // Check for node_id index
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_node_status_node_id'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($result);
    }

    public function testSetNodeStatus(): void
    {
        $this->statusRepo->setNodeStatus('node1', 'healthy');

        $status = $this->getStatusFromDb('node1');
        $this->assertNotNull($status);
        $this->assertEquals('node1', $status['node_id']);
        $this->assertEquals('healthy', $status['status']);
        $this->assertNotEmpty($status['created_at']);
    }

    public function testSetNodeStatusMultipleTimes(): void
    {
        // First set
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $firstStatus = $this->getStatusFromDb('node1');
        $this->assertEquals('healthy', $firstStatus['status']);

        // Update status (REPLACE INTO should replace the existing row)
        $this->statusRepo->setNodeStatus('node1', 'unhealthy');
        $updatedStatus = $this->getStatusFromDb('node1');
        $this->assertEquals('unhealthy', $updatedStatus['status']);

        // Verify only one row exists for node1
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM status WHERE node_id = ?");
        $stmt->execute(['node1']);
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
    }

    public function testGetNodeStatus(): void
    {
        $this->statusRepo->setNodeStatus('node1', 'maintenance');

        $status = $this->statusRepo->getNodeStatus('node1');
        $this->assertEquals('maintenance', $status);
    }

    public function testGetNodeStatusForNonExistentNode(): void
    {
        $status = $this->statusRepo->getNodeStatus('nonexistent');
        $this->assertEquals('unknown', $status);
    }

    public function testGetStatuses(): void
    {
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $this->statusRepo->setNodeStatus('node2', 'unhealthy');
        $this->statusRepo->setNodeStatus('node3', 'maintenance');

        $statuses = $this->statusRepo->getStatuses();

        $this->assertCount(3, $statuses);
        $this->assertIsArray($statuses);

        // Verify each status has required fields
        foreach ($statuses as $status) {
            $this->assertArrayHasKey('node_id', $status);
            $this->assertArrayHasKey('status', $status);
            $this->assertArrayHasKey('created_at', $status);
        }
    }

    public function testGetStatusesReturnsEmptyArrayWhenNoStatuses(): void
    {
        $statuses = $this->statusRepo->getStatuses();
        $this->assertIsArray($statuses);
        $this->assertEmpty($statuses);
    }

    public function testSetMultipleNodeStatuses(): void
    {
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $this->statusRepo->setNodeStatus('node2', 'unhealthy');
        $this->statusRepo->setNodeStatus('node3', 'maintenance');
        $this->statusRepo->setNodeStatus('node4', 'unknown');

        $statuses = $this->statusRepo->getStatuses();
        $this->assertCount(4, $statuses);

        $nodeIds = array_column($statuses, 'node_id');
        $this->assertContains('node1', $nodeIds);
        $this->assertContains('node2', $nodeIds);
        $this->assertContains('node3', $nodeIds);
        $this->assertContains('node4', $nodeIds);
    }

    public function testStatusIncludesTimestamp(): void
    {
        $this->statusRepo->setNodeStatus('node1', 'healthy');

        $status = $this->getStatusFromDb('node1');
        $this->assertNotNull($status);
        $this->assertArrayHasKey('created_at', $status);
        $this->assertNotEmpty($status['created_at']);

        // Verify timestamp format (YYYY-MM-DD HH:MM:SS)
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $status['created_at']);
    }

    public function testReplaceIntoUpdatesTimestamp(): void
    {
        // Set initial status
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $firstStatus = $this->getStatusFromDb('node1');
        $firstTimestamp = $firstStatus['created_at'];

        // Wait a moment to ensure different timestamp
        sleep(1);

        // Update status
        $this->statusRepo->setNodeStatus('node1', 'unhealthy');
        $updatedStatus = $this->getStatusFromDb('node1');
        $updatedTimestamp = $updatedStatus['created_at'];

        // The timestamp should be updated (REPLACE INTO creates a new row)
        $this->assertNotEquals($firstTimestamp, $updatedTimestamp);
    }

    public function testSetStatusWithAllAllowedValues(): void
    {
        $allowedStatuses = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

        foreach ($allowedStatuses as $index => $status) {
            $nodeId = 'node' . ($index + 1);
            $this->statusRepo->setNodeStatus($nodeId, $status);

            $retrievedStatus = $this->statusRepo->getNodeStatus($nodeId);
            $this->assertEquals($status, $retrievedStatus);
        }

        $allStatuses = $this->statusRepo->getStatuses();
        $this->assertCount(count($allowedStatuses), $allStatuses);
    }

    public function testGetStatusesReturnsFreshData(): void
    {
        // Initial statuses
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $this->statusRepo->setNodeStatus('node2', 'unhealthy');

        $statuses = $this->statusRepo->getStatuses();
        $this->assertCount(2, $statuses);

        // Add more statuses
        $this->statusRepo->setNodeStatus('node3', 'maintenance');

        $updatedStatuses = $this->statusRepo->getStatuses();
        $this->assertCount(3, $updatedStatuses);
    }

    public function testConcurrentStatusUpdates(): void
    {
        // Simulate multiple rapid updates to the same node
        $this->statusRepo->setNodeStatus('node1', 'healthy');
        $this->statusRepo->setNodeStatus('node1', 'unhealthy');
        $this->statusRepo->setNodeStatus('node1', 'maintenance');
        $this->statusRepo->setNodeStatus('node1', 'unknown');

        $finalStatus = $this->statusRepo->getNodeStatus('node1');
        $this->assertEquals('unknown', $finalStatus);

        // Verify only one row exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM status WHERE node_id = ?");
        $stmt->execute(['node1']);
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
    }
}
