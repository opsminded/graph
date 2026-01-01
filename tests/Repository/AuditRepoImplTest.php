<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Opsminded\Graph\AuditContext;
use Opsminded\Graph\Repository\GraphRepoInterface;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;
use Opsminded\Graph\Repository\AuditRepoImpl;

class AuditRepoImplTest extends TestCase
{
    private string $dbFilename;
    private ?PDO $pdo;
    private ?GraphRepoInterface $repo;

    protected function setUp(): void
    {
        $this->dbFilename = sys_get_temp_dir() . '/graphdb' . uniqid() . '.sqlite';
        $this->pdo = SqliteGraphRepoImpl::createConnection($this->dbFilename);
        $this->repo = new SqliteGraphRepoImpl($this->pdo);
        $this->repo = new AuditRepoImpl($this->pdo, $this->repo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->repo = null;

        if (file_exists($this->dbFilename)) {
            @unlink($this->dbFilename);
        }
    }

    private function getAuditLogs(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM audit ORDER BY created_at ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLatestAuditLog(): ?array
    {
        $stmt = $this->pdo->query("SELECT * FROM audit ORDER BY created_at DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function testSchemaInitialization(): void
    {
        // Verify audit table was created
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($result);
        $this->assertEquals('audit', $result['name']);
    }

    public function testSchemaHasCorrectIndexes(): void
    {
        // Check for entity index
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_audit_entity'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($result);

        // Check for created_at index
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_audit_created'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($result);
    }

    public function testGetNodeCreatesAuditLog(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->getNode('node1');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('node1', $log['entity_id']);
        $this->assertEquals('get_node', $log['action']);
    }

    public function testGetNodesCreatesAuditLog(): void
    {
        $this->repo->getNodes();

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('all', $log['entity_id']);
        $this->assertEquals('get_nodes', $log['action']);
    }

    public function testGetNodeExistsCreatesAuditLog(): void
    {
        $this->repo->getNodeExists('node1');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('node1', $log['entity_id']);
        $this->assertEquals('get_node_exists', $log['action']);
    }

    public function testInsertNodeCreatesAuditLogWithNewData(): void
    {
        $nodeData = ['category' => 'business', 'type' => 'application', 'name' => 'Test Node'];

        $this->repo->insertNode('node1', 'node1', 'business', 'application', $nodeData);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('node1', $log['entity_id']);
        $this->assertEquals('insert_node', $log['action']);
        $this->assertNull($log['old_data']);

        $newData = json_decode($log['new_data'], true);
        $this->assertEquals('business', $newData['category']);
        $this->assertEquals('application', $newData['type']);
        $this->assertEquals('Test Node', $newData['name']);
    }

    public function testUpdateNodeCreatesAuditLogWithOldAndNewData(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application', 'name' => 'Original']);

        // Clear previous audit logs to focus on update
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->updateNode('node1', 'node1', 'business', 'application', ['name' => 'Updated']);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('node1', $log['entity_id']);
        $this->assertEquals('update_node', $log['action']);

        $oldData = json_decode($log['old_data'], true);
        $this->assertArrayHasKey('data', $oldData);
        $this->assertEquals('Original', $oldData['data']['name']);

        $newData = json_decode($log['new_data'], true);
        $this->assertEquals('Updated', $newData['name']);
    }

    public function testDeleteNodeCreatesAuditLogWithOldData(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application', 'name' => 'To Delete']);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->deleteNode('node1');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('node', $log['entity_type']);
        $this->assertEquals('node1', $log['entity_id']);
        $this->assertEquals('delete_node', $log['action']);

        $oldData = json_decode($log['old_data'], true);
        $this->assertArrayHasKey('data', $oldData);
        $this->assertEquals('To Delete', $oldData['data']['name']);

        $this->assertNull($log['new_data']);
    }

    public function testGetEdgeCreatesAuditLog(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->getEdge('edge1');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('edge1', $log['entity_id']);
        $this->assertEquals('get_edge', $log['action']);
    }

    public function testGetEdgesCreatesAuditLog(): void
    {
        $this->repo->getEdges();

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('all', $log['entity_id']);
        $this->assertEquals('get_edges', $log['action']);
    }

    public function testGetEdgeExistsCreatesAuditLog(): void
    {
        $this->repo->getEdgeExistsById('node1-node2');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('node1-node2', $log['entity_id']);
        $this->assertEquals('get_edge_exists', $log['action']);
    }

    public function testInsertEdgeCreatesAuditLogWithNewData(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', 'business', 'application', ['category' => 'business', 'type' => 'application']);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $edgeData = ['weight' => 10];
        $this->repo->insertEdge('node1-node2', 'node1', 'node2', $edgeData);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('node1-node2', $log['entity_id']);
        $this->assertEquals('insert_edge', $log['action']);
        $this->assertNull($log['old_data']);

        $newData = json_decode($log['new_data'], true);
        $this->assertEquals(10, $newData['weight']);
    }

    public function testUpdateEdgeCreatesAuditLogWithOldAndNewData(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 10]);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->updateEdge('edge1', 'node1', 'node2', ['weight' => 20]);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('edge1', $log['entity_id']);
        $this->assertEquals('update_edge', $log['action']);

        $oldData = json_decode($log['old_data'], true);
        $this->assertArrayHasKey('data', $oldData);

        $newData = json_decode($log['new_data'], true);
        $this->assertEquals(20, $newData['weight']);
    }

    public function testDeleteEdgeCreatesAuditLogWithOldData(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 10]);

        // Clear previous audit logs
        $this->pdo->exec("DELETE FROM audit");

        $this->repo->deleteEdge('edge1');

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('edge', $log['entity_type']);
        $this->assertEquals('edge1', $log['entity_id']);
        $this->assertEquals('delete_edge', $log['action']);

        $oldData = json_decode($log['old_data'], true);
        $this->assertArrayHasKey('data', $oldData);

        $this->assertNull($log['new_data']);
    }

    public function testAuditLogsIncludeAuditContext(): void
    {
        AuditContext::set('testuser', '192.168.1.100');

        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertEquals('testuser', $log['user_id']);
        $this->assertEquals('192.168.1.100', $log['ip_address']);

        AuditContext::clear();
    }

    public function testAuditLogsHandleNullContext(): void
    {
        AuditContext::clear();

        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertNull($log['user_id']);
        $this->assertNull($log['ip_address']);
    }

    public function testMultipleOperationsCreateMultipleAuditLogs(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', 'infrastructure', 'server', ['category' => 'infrastructure', 'type' => 'server']);
        $this->repo->updateNode('node1', 'node1', 'business', 'application', ['name' => 'Updated']);
        $this->repo->deleteNode('node2');

        $logs = $this->getAuditLogs();
        $this->assertGreaterThanOrEqual(4, count($logs));

        // Verify action types
        $actions = array_column($logs, 'action');
        $this->assertContains('insert_node', $actions);
        $this->assertContains('update_node', $actions);
        $this->assertContains('delete_node', $actions);
    }

    public function testAuditLogsIncludeTimestamps(): void
    {
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application']);

        $log = $this->getLatestAuditLog();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('created_at', $log);
        $this->assertNotEmpty($log['created_at']);

        // Verify timestamp format
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $log['created_at']);
    }

    public function testOperationsDelegateCorrectly(): void
    {
        // Test that operations still work correctly through the decorator
        $this->repo->insertNode('node1', 'node1', 'business', 'application', ['category' => 'business', 'type' => 'application', 'name' => 'Test']);

        $node = $this->repo->getNode('node1');
        $this->assertNotNull($node);
        $this->assertEquals('Test', $node['data']['name']);

        $this->assertTrue($this->repo->getNodeExists('node1'));

        $this->repo->updateNode('node1', 'node1', 'business', 'application', ['name' => 'Updated']);
        $updatedNode = $this->repo->getNode('node1');
        $this->assertEquals('Updated', $updatedNode['data']['name']);

        $this->repo->deleteNode('node1');
        $this->assertFalse($this->repo->getNodeExists('node1'));
    }
}