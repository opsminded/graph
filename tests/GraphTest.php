<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Graph;
use Opsminded\Graph\NodeStatus;

class GraphTest extends TestCase
{
    private string $dbFile;
    private Graph $graph;

    protected function setUp(): void
    {
        $this->dbFile = sys_get_temp_dir() . '/graph_graph_test_' . uniqid() . '.db';
        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
        $this->graph = new Graph($this->dbFile);
    }

    protected function tearDown(): void
    {
        $backupDir = dirname($this->dbFile) . '/backups';
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($backupDir);
        }
        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
    }

    public function testAddNodeAndEdgeAndStatus()
    {
        $this->assertTrue($this->graph->addNode('n1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'N1'
        ]));
        $this->assertTrue($this->graph->nodeExists('n1'));

        $this->assertTrue($this->graph->addNode('n2', [
            'category' => 'infrastructure',
            'type' => 'server',
            'name' => 'N2'
        ]));
        $this->assertTrue($this->graph->addEdge('n1', 'n2'));
        $this->assertTrue($this->graph->edgeExists('n1', 'n2'));

        $this->assertTrue($this->graph->setNodeStatus('n1', 'healthy'));
        $ns = $this->graph->getNodeStatus('n1');
        $this->assertInstanceOf(NodeStatus::class, $ns);
        $this->assertSame('healthy', $ns->getStatus());

        $all = $this->graph->get();
        $this->assertArrayHasKey('nodes', $all);
        $this->assertArrayHasKey('edges', $all);
    }

    public function testUpdateNodeAndRemoveNodeEdgeCases()
    {
        // update non-existing node
        $this->assertFalse($this->graph->updateNode('noexist', ['name' => 'x']));

        // remove non-existing node
        $this->assertFalse($this->graph->removeNode('noexist'));

        // create node and update
        $this->assertTrue($this->graph->addNode('nn', [
            'category' => 'application',
            'type' => 'database',
            'name' => 'nn'
        ]));
        $this->assertTrue($this->graph->updateNode('nn', ['name' => 'nn2']));

        // add edge then remove_edges_from
        $this->assertTrue($this->graph->addNode('n3', [
            'category' => 'business',
            'type' => 'network',
            'name' => 'n3'
        ]));
        $this->assertTrue($this->graph->addEdge('nn', 'n3'));
    }

    public function testValidationRequiresCategoryAndType()
    {
        // Missing category
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Node category is required');
        $this->graph->addNode('node1', ['type' => 'server', 'name' => 'Test']);
    }

    public function testValidationRequiresType()
    {
        // Missing type
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Node type is required');
        $this->graph->addNode('node1', ['category' => 'business', 'name' => 'Test']);
    }

    public function testValidationRejectsInvalidCategory()
    {
        // Invalid category
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid category. Allowed values: business, application, infrastructure');
        $this->graph->addNode('node1', ['category' => 'invalid', 'type' => 'server']);
    }

    public function testValidationRejectsInvalidType()
    {
        // Invalid type
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid type. Allowed values: server, database, application, network');
        $this->graph->addNode('node1', ['category' => 'business', 'type' => 'invalid']);
    }

    public function testValidationOnUpdateRejectsInvalidValues()
    {
        // Create valid node
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Test'
        ]);

        // Try to update with invalid category
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid category');
        $this->graph->updateNode('node1', ['category' => 'invalid']);
    }


    public function testStatusReturnsUnknownForNodesWithoutStatus()
    {
        // Create nodes
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);
        $this->graph->addNode('node2', [
            'category' => 'infrastructure',
            'type' => 'server',
            'name' => 'Node 2'
        ]);

        // Set status only for node1
        $this->graph->setNodeStatus('node1', 'healthy');

        // Get all statuses
        $allStatuses = $this->graph->status();

        // Should return 2 statuses (one for each node)
        $this->assertCount(2, $allStatuses);

        // Find statuses by node_id
        $statusMap = [];
        foreach ($allStatuses as $status) {
            $statusMap[$status->getNodeId()] = $status->getStatus();
        }

        // node1 should have 'healthy' status
        $this->assertSame('healthy', $statusMap['node1']);

        // node2 should have 'unknown' status (default)
        $this->assertSame('unknown', $statusMap['node2']);
    }

    public function testSetNodeStatusValidatesAllowedValues()
    {
        // Create a node
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Test'
        ]);

        // Valid statuses should work
        $this->assertTrue($this->graph->setNodeStatus('node1', 'healthy'));
        $this->assertTrue($this->graph->setNodeStatus('node1', 'unhealthy'));
        $this->assertTrue($this->graph->setNodeStatus('node1', 'maintenance'));
        $this->assertTrue($this->graph->setNodeStatus('node1', 'unknown'));

        // Invalid status should throw exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid status. Allowed values: unknown, healthy, unhealthy, maintenance');
        $this->graph->setNodeStatus('node1', 'invalid_status');
    }

    public function testSetNodeStatusRejectsInvalidStatus()
    {
        // Create a node
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Test'
        ]);

        // Try various invalid statuses
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid status');
        $this->graph->setNodeStatus('node1', 'active'); // 'active' is not in allowed list
    }

    public function testRemoveEdge()
    {
        // Create two nodes
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);
        $this->graph->addNode('node2', [
            'category' => 'infrastructure',
            'type' => 'server',
            'name' => 'Node 2'
        ]);

        // Add edge
        $this->assertTrue($this->graph->addEdge('node1', 'node2'));
        $this->assertTrue($this->graph->edgeExists('node1', 'node2'));

        // Remove edge
        $this->assertTrue($this->graph->removeEdge('node1', 'node2'));
        $this->assertFalse($this->graph->edgeExists('node1', 'node2'));

        // Try to remove non-existent edge
        $this->assertFalse($this->graph->removeEdge('node1', 'node2'));
    }

    public function testRemoveNodeActuallyRemovesNode()
    {
        // Create node
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);
        $this->assertTrue($this->graph->nodeExists('node1'));

        // Remove node
        $this->assertTrue($this->graph->removeNode('node1'));
        $this->assertFalse($this->graph->nodeExists('node1'));

        // Verify node is really gone
        $this->assertFalse($this->graph->removeNode('node1'));
    }

    public function testAuditLogAndGetAuditHistory()
    {
        // Create a node to generate audit logs
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);

        // Update node to generate another audit log
        $this->graph->updateNode('node1', ['name' => 'Node 1 Updated']);

        // Get all audit history
        $history = $this->graph->getAuditHistory();
        $this->assertIsArray($history);
        $this->assertGreaterThanOrEqual(2, count($history));

        // Get audit history filtered by entity type
        $nodeHistory = $this->graph->getAuditHistory('node');
        $this->assertIsArray($nodeHistory);
        $this->assertGreaterThanOrEqual(2, count($nodeHistory));

        // Get audit history filtered by entity type and entity id
        $node1History = $this->graph->getAuditHistory('node', 'node1');
        $this->assertIsArray($node1History);
        $this->assertGreaterThanOrEqual(2, count($node1History));

        // Verify audit log entries have expected structure
        $this->assertArrayHasKey('entity_type', $node1History[0]);
        $this->assertArrayHasKey('entity_id', $node1History[0]);
        $this->assertArrayHasKey('action', $node1History[0]);
    }

    public function testAuditLogPublicMethod()
    {
        // Test public auditLog method directly
        $result = $this->graph->auditLog(
            'test_entity',
            'test_id',
            'test_action',
            ['old' => 'data'],
            ['new' => 'data'],
            'test_user',
            '127.0.0.1'
        );

        $this->assertTrue($result);

        // Verify it was logged
        $history = $this->graph->getAuditHistory('test_entity', 'test_id');
        $this->assertCount(1, $history);
        $this->assertSame('test_action', $history[0]['action']);
    }

    public function testGetNodeStatusHistory()
    {
        // Create a node
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);

        // Set multiple statuses over time
        $this->graph->setNodeStatus('node1', 'healthy');
        $this->graph->setNodeStatus('node1', 'unhealthy');
        $this->graph->setNodeStatus('node1', 'maintenance');

        // Get status history
        $history = $this->graph->getNodeStatusHistory('node1');

        // Should have 3 status entries
        $this->assertCount(3, $history);

        // All should be NodeStatus objects
        $statuses = [];
        foreach ($history as $status) {
            $this->assertInstanceOf(NodeStatus::class, $status);
            $this->assertSame('node1', $status->getNodeId());
            $statuses[] = $status->getStatus();
        }

        // Verify all three statuses are present (order may vary due to same timestamp)
        $this->assertContains('healthy', $statuses);
        $this->assertContains('unhealthy', $statuses);
        $this->assertContains('maintenance', $statuses);
    }

    public function testGetNodeStatusHistoryEmptyForNonExistentNode()
    {
        // Get status history for non-existent node
        $history = $this->graph->getNodeStatusHistory('nonexistent');
        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }

    public function testSetNodeStatusReturnsFalseForNonExistentNode()
    {
        // Try to set status on non-existent node
        $result = $this->graph->setNodeStatus('nonexistent', 'healthy');
        $this->assertFalse($result);
    }

    public function testGetNodeStatusReturnsNullForNonExistentNode()
    {
        // Get status for non-existent node
        $status = $this->graph->getNodeStatus('nonexistent');
        $this->assertNull($status);
    }

    public function testGetNodeStatusReturnsNullForNodeWithoutStatus()
    {
        // Create node without setting status
        $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);

        // Should return null since no status was set
        $status = $this->graph->getNodeStatus('node1');
        $this->assertNull($status);
    }

    public function testAddNodeIsIdempotent()
    {
        // Add node first time
        $result1 = $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Node 1'
        ]);
        $this->assertTrue($result1);

        // Add same node again - should return true (idempotent)
        $result2 = $this->graph->addNode('node1', [
            'category' => 'business',
            'type' => 'application',
            'name' => 'Different Name'
        ]);
        $this->assertTrue($result2);
    }

    public function testEdgeExistsReturnsFalseForNonExistentEdge()
    {
        // Check for non-existent edge
        $this->assertFalse($this->graph->edgeExists('node1', 'node2'));
    }

    public function testGetReturnsEmptyArraysForEmptyGraph()
    {
        // Get empty graph
        $graph = $this->graph->get();
        $this->assertArrayHasKey('nodes', $graph);
        $this->assertArrayHasKey('edges', $graph);
        $this->assertEmpty($graph['nodes']);
        $this->assertEmpty($graph['edges']);
    }
}
