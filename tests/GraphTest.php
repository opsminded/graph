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

    public function testGetAllowedValues()
    {
        $categories = Graph::getAllowedCategories();
        $this->assertSame(['business', 'application', 'infrastructure'], $categories);

        $types = Graph::getAllowedTypes();
        $this->assertSame(['server', 'database', 'application', 'network'], $types);
    }
}
