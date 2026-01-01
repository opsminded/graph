<?php

declare(strict_types=1);

use Dom\Sqlite;
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Repository\GraphRepoInterface;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;

class SqliteGraphRepoImplTest extends TestCase
{
    private string $dbFile;
    private ?PDO $pdo;
    private ?GraphRepoInterface $repo;

    protected function setUp(): void
    {
        $this->dbFile = sys_get_temp_dir() . '/graph_repo_test_' . uniqid() . '.db';
        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }

        $this->pdo = SqliteGraphRepoImpl::createConnection($this->dbFile);
        $this->repo = new SqliteGraphRepoImpl($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->repo = null;
        
        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
    }

    // ========================================
    // Node Methods - insertNode()
    // ========================================

    public function testInsertNodeSuccessfully(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $data = ['name' => 'Test Node', 'value' => 42];

        $result = $this->repo->insertNode($id, $category, $type,     $data);

        $this->assertTrue($result);
        $this->assertTrue($this->repo->getNodeExists($id));
    }

    public function testInsertNodeWithDuplicateIdUsesInsertOrIgnore(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $data1 = ['name' => 'First'];
        $data2 = ['name' => 'Second'];

        $this->repo->insertNode($id, $category, $type, $data1);
        $result = $this->repo->insertNode($id, $category, $type, $data2);

        $this->assertTrue($result); // INSERT OR IGNORE still returns true
        $node = $this->repo->getNode($id);
        $this->assertSame('First', $node['data']['name']); // Original data preserved
    }

    // ========================================
    // Node Methods - getNode()
    // ========================================

    public function testGetNodeReturnsDataWhenNodeExists(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $data = ['name' => 'Test Node', 'value' => 42, 'nested' => ['key' => 'value']];

        $this->repo->insertNode($id, $category, $type, $data);
        $result = $this->repo->getNode($id);

        $data['id'] = $id;
        $this->assertSame(['data' => $data], $result);
    }

    public function testGetNodeReturnsNullWhenNodeDoesNotExist(): void
    {
        $this->assertNull($this->repo->getNode('nonexistent'), 'Node should not exist.');
    }

    // ========================================
    // Node Methods - getNodes()
    // ========================================

    public function testGetNodesReturnsAllNodes(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertNode('node3', $category, $type, ['name' => 'Third']);

        $nodes = $this->repo->getNodes();

        $this->assertCount(3, $nodes);
        $this->assertArrayHasKey('id', $nodes[0]['data']);
        $this->assertArrayHasKey('name', $nodes[0]['data']);
    }

    public function testGetNodesReturnsEmptyArrayWhenNoNodes(): void
    {
        $nodes = $this->repo->getNodes();

        $this->assertIsArray($nodes);
        $this->assertEmpty($nodes);
    }

    // ========================================
    // Node Methods - getNodeExists()
    // ========================================

    public function testGetNodeExistsReturnsTrueWhenNodeExists(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode($id, $category, $type, ['name' => 'Test']);

        $result = $this->repo->getNodeExists($id);

        $this->assertTrue($result);
    }

    public function testGetNodeExistsReturnsFalseWhenNodeDoesNotExist(): void
    {
        $result = $this->repo->getNodeExists('nonexistent');

        $this->assertFalse($result);
    }

    // ========================================
    // Node Methods - updateNode()
    // ========================================

    public function testUpdateNodeSuccessfullyUpdatesData(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode($id, $category, $type, ['name' => 'Original', 'value' => 1]);

        $newData = ['name' => 'Updated', 'value' => 2];
        $result = $this->repo->updateNode($id, $category, $type, $newData);

        $this->assertTrue($result);
        $updated = $this->repo->getNode($id);
        $this->assertSame('Updated', $updated['data']['name']);
        $this->assertSame(2, $updated['data']['value']);
    }

    public function testUpdateNodeReturnsFalseWhenNodeDoesNotExist(): void
    {
        $category = 'business';
        $type = 'server';
        $result = $this->repo->updateNode('nonexistent', $category, $type, ['name' => 'Test']);

        $this->assertFalse($result);
    }

    // ========================================
    // Node Methods - deleteNode()
    // ========================================

    public function testDeleteNodeSuccessfullyRemovesNode(): void
    {
        $id = 'node1';
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode($id, $category, $type, ['name' => 'Test']);

        $result = $this->repo->deleteNode($id);

        $this->assertTrue($result);
        $this->assertFalse($this->repo->getNodeExists($id));
    }

    public function testDeleteNodeCascadesDeletesEdges(): void
    {
        $category = 'business';
        $type = 'server';

        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $this->assertTrue($this->repo->getEdgeExistsById('node1-node2'));

        $this->repo->deleteNode('node1');

        $this->assertFalse($this->repo->getEdgeExistsById('node1-node2'));
    }

    // ========================================
    // Edge Methods - insertEdge()
    // ========================================

    public function testInsertEdgeSuccessfully(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);

        $result = $this->repo->insertEdge('node1-node2', 'node1', 'node2');
        $this->assertTrue($result);
        $this->assertTrue($this->repo->getEdgeExistsById('node1-node2'));
    }

    public function testInsertEdgeWithDuplicateUsesInsertOrIgnore(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);

        $this->repo->insertEdge('node1-node2', 'node1', 'node2');
        $result = $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $this->assertTrue($result); // INSERT OR IGNORE still returns true
    }

    // ========================================
    // Edge Methods - getEdge()
    // ========================================

    public function testGetEdgeReturnsEdgeWhenExists(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $edge = $this->repo->getEdge('node1-node2');
        $this->assertNotEmpty($edge);
        $this->assertSame('node1', $edge['data']['source']);
        $this->assertSame('node2', $edge['data']['target']);
    }

    public function testGetEdgeReturnsNullWhenEdgeDoesNotExist(): void
    {
        $this->assertNull($this->repo->getEdge('nonexistent1-node2'), 'Edge should not exist.');
    }

    // ========================================
    // Edge Methods - getEdges()
    // ========================================

    public function testGetEdgesReturnsAllEdges(): void
    {
        $category = 'business';
        $type = 'server';

        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertNode('node3', $category, $type, ['name' => 'Third']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');
        $this->repo->insertEdge('node2-node3', 'node2', 'node3');
        $edges = $this->repo->getEdges();

        $this->assertCount(2, $edges);
        $this->assertArrayHasKey('source', $edges[0]['data']);
        $this->assertArrayHasKey('target', $edges[0]['data']);
    }

    public function testGetEdgesReturnsEmptyArrayWhenNoEdges(): void
    {
        $edges = $this->repo->getEdges();

        $this->assertIsArray($edges);
        $this->assertEmpty($edges);
    }

    // ========================================
    // Edge Methods - getEdgeExists()
    // ========================================

    public function testGetEdgeExistsReturnsTrueForDirectEdge(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $result = $this->repo->getEdgeExistsById('node1-node2');

        $this->assertTrue($result);
    }

    public function testGetEdgeExistsReturnsFalseForReverseEdge(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        // Verify edges are directional (not bidirectional)
        // The reverse edge should NOT exist
        $result = $this->repo->getEdgeExistsById('node2-node1');

        $this->assertFalse($result);
    }

    public function testGetEdgeExistsReturnsFalseWhenEdgeDoesNotExist(): void
    {
        $result = $this->repo->getEdgeExistsById('nonexistent1', 'nonexistent2');

        $this->assertFalse($result);
    }

    // ========================================
    // Edge Methods - updateEdge()
    // ========================================

    public function testUpdateEdgeSuccessfullyUpdatesData(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $edgeData = ['weight' => 5, 'type' => 'connection'];
        $result = $this->repo->updateEdge('node1-node2', 'node1', 'node2', $edgeData);

        $this->assertTrue($result);
    }

    public function testUpdateEdgeReturnsSuccessEvenWhenEdgeDoesNotExist(): void
    {
        $id = 'nonexistent1-nonexistent2';
        $result = $this->repo->updateEdge($id, 'nonexistent1', 'nonexistent2', ['weight' => 1]);
        $this->assertTrue($result);
    }

    // ========================================
    // Edge Methods - deleteEdge()
    // ========================================

    public function testDeleteEdgeSuccessfullyRemovesEdge(): void
    {
        $category = 'business';
        $type = 'server';

        $this->repo->insertNode('node1', $category, $type, ['name' => 'First']);
        $this->repo->insertNode('node2', $category, $type, ['name' => 'Second']);
        $this->repo->insertEdge('node1-node2', 'node1', 'node2');

        $result = $this->repo->deleteEdge('node1-node2');
        $this->assertTrue($result);
        $this->assertFalse($this->repo->getEdgeExistsById('node1', 'node2'));
    }

    // ========================================
    // Schema & Infrastructure Tests
    // ========================================

    public function testSchemaInitializationCreatesTablesCorrectly(): void
    {
        // Test that we can perform operations without errors (schema was initialized)
        $edges = $this->repo->getEdges();
        $this->assertIsArray($edges);

        $nodes = $this->repo->getNodes();
        $this->assertIsArray($nodes);

        // Verify we can check for existence without errors
        $exists = $this->repo->getNodeExists('test');
        $this->assertFalse($exists);
    }

    // ========================================
    // Integration & Complex Scenarios
    // ========================================

    public function testCompleteNodeLifecycle(): void
    {
        $id = 'lifecycle_node';
        $category = 'business';
        $type = 'server';
        $initialData = ['name' => 'Initial', 'status' => 'active'];
        $updatedData = ['name' => 'Updated', 'status' => 'inactive'];

        // Create
        $this->assertFalse($this->repo->getNodeExists($id));
        $this->assertTrue($this->repo->insertNode($id, $category, $type, $initialData));
        $this->assertTrue($this->repo->getNodeExists($id));

        // Read
        $node = $this->repo->getNode($id);
        $this->assertSame('Initial', $node['data']['name']);
        $this->assertSame('active', $node['data']['status']);

        // Update
        $this->assertTrue($this->repo->updateNode($id, $category, $type, $updatedData));
        $node = $this->repo->getNode($id);
        $this->assertSame('Updated', $node['data']['name']);
        $this->assertSame('inactive', $node['data']['status']);

        // Delete
        $this->assertTrue($this->repo->deleteNode($id));
        $this->assertFalse($this->repo->getNodeExists($id));

        $this->assertNull($this->repo->getNode($id));
    }

    public function testMultipleNodesAndEdgesGraph(): void
    {
        $category = 'business';
        $type = 'server';
        
        // Create a small graph: A -> B -> C, A -> C
        $this->repo->insertNode('A', $category, $type, ['name' => 'Node A']);
        $this->repo->insertNode('B', $category, $type, ['name' => 'Node B']);
        $this->repo->insertNode('C', $category, $type, ['name' => 'Node C']);
        $this->repo->insertEdge('A-B', 'A', 'B');
        $this->repo->insertEdge('B-C', 'B', 'C');
        $this->repo->insertEdge('A-C', 'A', 'C');

        $nodes = $this->repo->getNodes();
        $edges = $this->repo->getEdges();

        $this->assertCount(3, $nodes);
        $this->assertCount(3, $edges);

        // Verify all edges exist
        $this->assertTrue($this->repo->getEdgeExistsById('A-B'));
        $this->assertTrue($this->repo->getEdgeExistsById('B-C'));
        $this->assertTrue($this->repo->getEdgeExistsById('A-C'));
    }

    public function testJsonEncodingPreservesUnicodeCharacters(): void
    {
        $id = 'unicode_node';
        $category = 'business';
        $type = 'server';
        $data = [
            'name' => 'Test 测试',
            'emoji' => '🚀',
            'special' => 'Café résumé'
        ];

        $this->repo->insertNode($id, $category, $type, $data);
        $retrieved = $this->repo->getNode($id);

        $this->assertSame('Test 测试', $retrieved['data']['name']);
        $this->assertSame('🚀', $retrieved['data']['emoji']);
        $this->assertSame('Café résumé', $retrieved['data']['special']);
    }
}
