<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Service\BasicGraphServiceImpl;
use Opsminded\Graph\Service\GraphServiceInterface;
use Opsminded\Graph\Repository\GraphRepoInterface;
use Opsminded\Graph\Model\Node;
use Opsminded\Graph\Model\Nodes;
use Opsminded\Graph\Model\Edge;
use Opsminded\Graph\Model\Edges;
use Opsminded\Graph\Model\Graph;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;

class BasicGraphServiceImplTest extends TestCase
{
    private ?string $dbPath;
    private ?PDO $pdo;
    private ?GraphRepoInterface $repo;
    private ?GraphServiceInterface $service;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/graph_repo_test_' . uniqid() . '.db';
        $this->pdo = SqliteGraphRepoImpl::createConnection($this->dbPath);
        $this->repo = new SqliteGraphRepoImpl($this->pdo);
        $this->service = new BasicGraphServiceImpl($this->repo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->repo = null;
        $this->service = null;

        if (file_exists($this->dbPath)) {
            @unlink($this->dbPath);
        }
    }

    // ========================================
    // Graph Methods - getGraph()
    // ========================================

    public function testGetGraphReturnsGraphWithNodesAndEdges(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', ['name' => 'First']);
        $this->repo->insertNode('node2', 'Node 2', 'application', 'database', ['name' => 'Second']);
        $this->repo->insertEdge('edge1', 'node1', 'node2');

        $graph = $this->service->getGraph();

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertCount(2, $graph->getNodes());
        $this->assertCount(1, $graph->getEdges());
    }

    public function testGetGraphReturnsEmptyGraphWhenNoData(): void
    {
        $graph = $this->service->getGraph();

        $this->assertInstanceOf(Graph::class, $graph);
        $this->assertEmpty($graph->getNodes());
        $this->assertEmpty($graph->getEdges());
    }

    // ========================================
    // Node Methods - getNode()
    // ========================================

    public function testGetNodeReturnsNodeWhenExists(): void
    {
        $nodeId = 'node1';
        $this->repo->insertNode($nodeId, 'Test Node', 'business', 'server', ['name' => 'John', 'age' => 30]);

        $node = $this->service->getNode($nodeId);

        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame($nodeId, $node->getId());
        $this->assertSame('Test Node', $node->getLabel());
        $this->assertSame('business', $node->getCategory());
        $this->assertSame('server', $node->getType());
        $this->assertSame('John', $node->getData()['name']);
        $this->assertSame(30, $node->getData()['age']);
    }

    public function testGetNodeReturnsNullWhenNotExists(): void
    {
        $node = $this->service->getNode('nonexistent');

        $this->assertNull($node);
    }

    // ========================================
    // Node Methods - getNodes()
    // ========================================

    public function testGetNodesReturnsNodesCollection(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', ['name' => 'First']);
        $this->repo->insertNode('node2', 'Node 2', 'application', 'database', ['name' => 'Second']);
        $this->repo->insertNode('node3', 'Node 3', 'infrastructure', 'network', ['name' => 'Third']);

        $nodes = $this->service->getNodes();

        $this->assertInstanceOf(Nodes::class, $nodes);

        $nodeArray = iterator_to_array($nodes);
        $this->assertCount(3, $nodeArray);

        $this->assertInstanceOf(Node::class, $nodeArray[0]);
        /** @var Node $node1 */
        $node1 = $nodeArray[0];
        $this->assertSame('node1', $node1->getId());
        $this->assertSame('Node 1', $node1->getLabel());

        $this->assertInstanceOf(Node::class, $nodeArray[1]);
        /** @var Node $node2 */
        $node2 = $nodeArray[1];
        $this->assertSame('node2', $node2->getId());

        $this->assertInstanceOf(Node::class, $nodeArray[2]);
        /** @var Node $node3 */
        $node3 = $nodeArray[2];
        $this->assertSame('node3', $node3->getId());
    }

    public function testGetNodesReturnsEmptyCollectionWhenNoNodes(): void
    {
        $nodes = $this->service->getNodes();

        $this->assertInstanceOf(Nodes::class, $nodes);
        $this->assertCount(0, iterator_to_array($nodes));
    }

    // ========================================
    // Node Methods - getNodeExists()
    // ========================================

    public function testGetNodeExistsReturnsTrueWhenNodeExists(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', ['name' => 'Test']);

        $result = $this->service->getNodeExists('node1');

        $this->assertTrue($result);
    }

    public function testGetNodeExistsReturnsFalseWhenNodeDoesNotExist(): void
    {
        $result = $this->service->getNodeExists('nonexistent');

        $this->assertFalse($result);
    }

    // ========================================
    // Node Methods - insertNode()
    // ========================================

    public function testInsertNodeSuccessfully(): void
    {
        $node = new Node('node1', 'Test Node', 'business', 'server', ['name' => 'Test']);

        $result = $this->service->insertNode($node);

        $this->assertTrue($result);
        $this->assertTrue($this->repo->getNodeExists('node1'));
    }

    public function testInsertNodeWithDuplicateId(): void
    {
        $node1 = new Node('node1', 'First Node', 'business', 'server', ['name' => 'First']);
        $node2 = new Node('node1', 'Second Node', 'business', 'server', ['name' => 'Second']);

        $this->service->insertNode($node1);
        $result = $this->service->insertNode($node2);

        $this->assertTrue($result);
        $retrievedNode = $this->service->getNode('node1');
        $this->assertSame('First', $retrievedNode->getData()['name']);
    }

    // ========================================
    // Node Methods - updateNode()
    // ========================================

    public function testUpdateNodeSuccessfully(): void
    {
        $this->repo->insertNode('node1', 'Original Node', 'business', 'server', ['name' => 'Original']);
        $node = new Node('node1', 'Updated Node', 'application', 'database', ['name' => 'Updated']);

        $result = $this->service->updateNode($node);

        $this->assertTrue($result);
        $updatedNode = $this->service->getNode('node1');
        $this->assertSame('Updated Node', $updatedNode->getLabel());
        $this->assertSame('application', $updatedNode->getCategory());
        $this->assertSame('database', $updatedNode->getType());
        $this->assertSame('Updated', $updatedNode->getData()['name']);
    }

    public function testUpdateNodeReturnsFalseWhenNodeDoesNotExist(): void
    {
        $node = new Node('nonexistent', 'Updated Node', 'business', 'server', ['name' => 'Updated']);

        $result = $this->service->updateNode($node);

        $this->assertFalse($result);
    }

    // ========================================
    // Node Methods - deleteNode()
    // ========================================

    public function testDeleteNodeSuccessfully(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', ['name' => 'Test']);

        $result = $this->service->deleteNode('node1');

        $this->assertTrue($result);
        $this->assertFalse($this->repo->getNodeExists('node1'));
    }

    public function testDeleteNodeReturnsTrueEvenWhenNodeDoesNotExist(): void
    {
        $result = $this->service->deleteNode('nonexistent');

        $this->assertTrue($result);
    }

    public function testDeleteNodeCascadesDeletesEdges(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', ['name' => 'First']);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', ['name' => 'Second']);
        $this->repo->insertEdge('edge1', 'node1', 'node2');

        $this->assertTrue($this->repo->getEdgeExistsById('edge1'));

        $this->service->deleteNode('node1');

        $this->assertFalse($this->repo->getEdgeExistsById('edge1'));
    }

    // ========================================
    // Edge Methods - getEdge()
    // ========================================

    public function testGetEdgeReturnsEdgeWhenExists(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $this->repo->insertEdge('node1@node2', 'node1', 'node2', ['weight' => 5]);

        $edge = $this->service->getEdge('node1', 'node2');

        $this->assertInstanceOf(Edge::class, $edge);
        $this->assertSame('node1', $edge->getSourceNodeId());
        $this->assertSame('node2', $edge->getTargetNodeId());
    }

    public function testGetEdgeReturnsNullWhenNotExists(): void
    {
        $edge = $this->service->getEdge('node1', 'node2');

        $this->assertNull($edge);
    }

    // ========================================
    // Edge Methods - getEdges()
    // ========================================

    public function testGetEdgesReturnsEdgesCollection(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $this->repo->insertNode('node3', 'Node 3', 'business', 'server', []);
        $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 1]);
        $this->repo->insertEdge('edge2', 'node2', 'node3', ['weight' => 2]);

        $edges = $this->service->getEdges();

        $this->assertInstanceOf(Edges::class, $edges);

        $edgeArray = iterator_to_array($edges);
        $this->assertCount(2, $edgeArray);

        $this->assertInstanceOf(Edge::class, $edgeArray[0]);
        /** @var Edge $edge1 */
        $edge1 = $edgeArray[0];
        $this->assertSame('edge1', $edge1->getId());
        $this->assertSame('node1', $edge1->getSourceNodeId());
        $this->assertSame('node2', $edge1->getTargetNodeId());

        $this->assertInstanceOf(Edge::class, $edgeArray[1]);
        /** @var Edge $edge2 */
        $edge2 = $edgeArray[1];
        $this->assertSame('edge2', $edge2->getId());
    }

    public function testGetEdgesReturnsEmptyCollectionWhenNoEdges(): void
    {
        $edges = $this->service->getEdges();

        $this->assertInstanceOf(Edges::class, $edges);
        $this->assertCount(0, iterator_to_array($edges));
    }

    // ========================================
    // Edge Methods - getEdgeExists()
    // ========================================

    public function testGetEdgeExistsReturnsTrueWhenEdgeExists(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $this->repo->insertEdge('edge1', 'node1', 'node2');

        $result = $this->service->getEdgeExists('node1', 'node2');

        $this->assertTrue($result);
    }

    public function testGetEdgeExistsReturnsFalseWhenEdgeDoesNotExist(): void
    {
        $result = $this->service->getEdgeExists('node1', 'node2');

        $this->assertFalse($result);
    }

    // ========================================
    // Edge Methods - insertEdge()
    // ========================================

    public function testInsertEdgeSuccessfully(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $edge = new Edge('edge1', 'node1', 'node2', ['weight' => 5]);

        $result = $this->service->insertEdge($edge);

        $this->assertTrue($result);
        $this->assertTrue($this->repo->getEdgeExistsByNodes('node1', 'node2'));
    }

    public function testInsertEdgeGeneratesIdFromSourceAndTarget(): void
    {
        $this->repo->insertNode('source1', 'Source 1', 'business', 'server', []);
        $this->repo->insertNode('target1', 'Target 1', 'business', 'server', []);
        $edge = new Edge('custom-id', 'source1', 'target1', []);

        $result = $this->service->insertEdge($edge);

        $this->assertTrue($result);
        $this->assertTrue($this->repo->getEdgeExistsById('source1@target1'));
    }

    public function testInsertEdgeWithDuplicateNodes(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $edge1 = new Edge('edge1', 'node1', 'node2', []);
        $edge2 = new Edge('edge2', 'node1', 'node2', []);

        $this->service->insertEdge($edge1);
        $result = $this->service->insertEdge($edge2);

        $this->assertTrue($result);
    }

    // ========================================
    // Edge Methods - updateEdge()
    // ========================================

    public function testUpdateEdgeSuccessfully(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 5]);

        $edge = new Edge('edge1', 'node1', 'node2', ['weight' => 10]);
        $result = $this->service->updateEdge($edge);

        $this->assertTrue($result);
    }

    public function testUpdateEdgeGeneratesIdFromSourceAndTarget(): void
    {
        $this->repo->insertNode('source2', 'Source 2', 'business', 'server', []);
        $this->repo->insertNode('target2', 'Target 2', 'business', 'server', []);
        $this->repo->insertEdge('edge1', 'source2', 'target2');

        $edge = new Edge('original-id', 'source2', 'target2', ['updated' => true]);
        $result = $this->service->updateEdge($edge);

        $this->assertTrue($result);
    }

    public function testUpdateEdgeSucceedsEvenWhenEdgeDoesNotExist(): void
    {
        $edge = new Edge('edge1', 'node1', 'node2', []);

        $result = $this->service->updateEdge($edge);

        $this->assertTrue($result);
    }

    // ========================================
    // Edge Methods - deleteEdge()
    // ========================================

    public function testDeleteEdgeSuccessfully(): void
    {
        $this->repo->insertNode('node1', 'Node 1', 'business', 'server', []);
        $this->repo->insertNode('node2', 'Node 2', 'business', 'server', []);
        $this->repo->insertEdge('node1@node2', 'node1', 'node2');

        $result = $this->service->deleteEdge('node1', 'node2');

        $this->assertTrue($result);
        $this->assertFalse($this->repo->getEdgeExistsByNodes('node1', 'node2'));
    }

    public function testDeleteEdgeReturnsTrueEvenWhenEdgeDoesNotExist(): void
    {
        $result = $this->service->deleteEdge('node1', 'node2');

        $this->assertTrue($result);
    }

    // ========================================
    // Integration & Complex Scenarios
    // ========================================

    public function testServiceImplementsGraphServiceInterface(): void
    {
        $this->assertInstanceOf(GraphServiceInterface::class, $this->service);
    }

    public function testConstructorAcceptsGraphRepoInterface(): void
    {
        $mockRepo = $this->createMock(GraphRepoInterface::class);
        $service = new BasicGraphServiceImpl($mockRepo);

        $this->assertInstanceOf(BasicGraphServiceImpl::class, $service);
    }

    public function testCompleteNodeLifecycle(): void
    {
        $node = new Node('lifecycle1', 'Lifecycle Node', 'business', 'server', ['status' => 'active']);

        $this->assertFalse($this->service->getNodeExists('lifecycle1'));

        $this->service->insertNode($node);
        $this->assertTrue($this->service->getNodeExists('lifecycle1'));

        $retrievedNode = $this->service->getNode('lifecycle1');
        $this->assertSame('Lifecycle Node', $retrievedNode->getLabel());
        $this->assertSame('active', $retrievedNode->getData()['status']);

        $updatedNode = new Node('lifecycle1', 'Updated Lifecycle', 'application', 'database', ['status' => 'inactive']);
        $this->service->updateNode($updatedNode);

        $retrievedNode = $this->service->getNode('lifecycle1');
        $this->assertSame('Updated Lifecycle', $retrievedNode->getLabel());
        $this->assertSame('application', $retrievedNode->getCategory());
        $this->assertSame('inactive', $retrievedNode->getData()['status']);

        $this->service->deleteNode('lifecycle1');
        $this->assertFalse($this->service->getNodeExists('lifecycle1'));
        $this->assertNull($this->service->getNode('lifecycle1'));
    }

    public function testCompleteGraphWorkflow(): void
    {
        $nodeA = new Node('A', 'Node A', 'business', 'server', ['name' => 'Alice']);
        $nodeB = new Node('B', 'Node B', 'application', 'database', ['name' => 'Bob']);
        $nodeC = new Node('C', 'Node C', 'infrastructure', 'network', ['name' => 'Charlie']);

        $this->service->insertNode($nodeA);
        $this->service->insertNode($nodeB);
        $this->service->insertNode($nodeC);

        $edgeAB = new Edge('edge-ab', 'A', 'B', ['weight' => 1]);
        $edgeBC = new Edge('edge-bc', 'B', 'C', ['weight' => 2]);
        $edgeAC = new Edge('edge-ac', 'A', 'C', ['weight' => 3]);

        $this->service->insertEdge($edgeAB);
        $this->service->insertEdge($edgeBC);
        $this->service->insertEdge($edgeAC);

        $graph = $this->service->getGraph();
        $this->assertCount(3, $graph->getNodes());
        $this->assertCount(3, $graph->getEdges());

        $nodes = $this->service->getNodes();
        $this->assertCount(3, iterator_to_array($nodes));

        $edges = $this->service->getEdges();
        $this->assertCount(3, iterator_to_array($edges));

        $this->assertTrue($this->service->getEdgeExists('A', 'B'));
        $this->assertTrue($this->service->getEdgeExists('B', 'C'));
        $this->assertTrue($this->service->getEdgeExists('A', 'C'));
    }
}
