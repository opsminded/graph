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
        $this->assertTrue($this->graph->addNode('n1', ['name' => 'N1']));
        $this->assertTrue($this->graph->nodeExists('n1'));

        $this->assertTrue($this->graph->addNode('n2', ['name' => 'N2']));
        $this->assertTrue($this->graph->addEdge('e1', 'n1', 'n2', ['label' => 'link']));
        $this->assertTrue($this->graph->edgeExistsById('e1'));

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
        $this->assertTrue($this->graph->addNode('nn', ['name' => 'nn']));
        $this->assertTrue($this->graph->updateNode('nn', ['name' => 'nn2']));

        // add edge then remove_edges_from
        $this->assertTrue($this->graph->addNode('n3', ['name' => 'n3']));
        $this->assertTrue($this->graph->addEdge('ezz', 'nn', 'n3', ['label' => 'l']));
        $this->assertTrue($this->graph->removeEdgesFrom('nn'));
    }
}
