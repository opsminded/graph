<?php

declare(strict_types=1);

class TestService extends TestAbstractTest
{
    private ?PDO $pdo;

    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;

    private ?DatabaseInterface $graphDB;
    private ?ServiceInterface $service;

    public function up(): void
    {
        $this->pdo = Database::createConnection('sqlite::memory:');
        
        $this->databaseLogger = new Logger();
        $this->graphDB = new Database($this->pdo, $this->databaseLogger);

        $this->serviceLogger = new Logger();
        $this->service = new Service($this->graphDB, $this->serviceLogger);
    }

    public function down(): void
    {
        $this->service = null;
        $this->serviceLogger = null;
        $this->graphDB = null;
        $this->databaseLogger = null;
        $this->pdo = null;
    }

    public function testGetUser(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $user = $this->service->getUser('maria');
        if ($user !== null) {
            throw new Exception('error on testGetUser');
        }

        $user = $this->service->getUser('admin');
        
        if ($user->getId() !== 'admin' || $user->getGroup()->getId() !== 'admin') {
            throw new Exception('error on testGetUser');
        }
    }
    
    public function testInsertUser(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $user = $this->service->getUser('maria');
        if ($user !== null) {
            throw new Exception('error on testInsertUser');
        }
        $this->service->insertUser(new ModelUser('maria', new ModelGroup('contributor')));
        
        try {
            $this->service->insertUser(new ModelUser('maria', new ModelGroup('contributor')));
        } catch (Exception $e) {
            return;
        }
        throw new Exception('error on testInsertUser');
    }
    
    public function testUpdateUser(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $this->service->insertUser(new ModelUser('maria', new ModelGroup('contributor')));
        $this->service->updateUser(new ModelUser('maria', new ModelGroup('admin')));
        $user = $this->service->getUser('maria');
        if ($user->getId() !== 'maria' || $user->getGroup()->getId() !== 'admin') {
            throw new Exception('error on testUpdateUser');
        }
        if ($this->service->updateUser(new ModelUser('pedro', new ModelGroup('admin')))) {
            throw new Exception('error on testUpdateUser');
        }
    }

    public function testGetCategories(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $categories = $this->service->getCategories();
        if (count($categories) === 0) {
            throw new Exception('error on testGetCategories - should not be empty');
        }
        $foundBusiness = false;
        foreach ($categories as $category) {
            if ($category->id === 'business') {
                $foundBusiness = true;
                break;
            }
        }
        if (!$foundBusiness) {
            throw new Exception('error on testGetCategories - business category not found');
        }

        $this->service->insertCategory(new ModelCategory('custom', 'Custom Category', 'circle', 100, 100));
        $categories = $this->service->getCategories();
        $foundCustom = false;
        foreach ($categories as $category) {
            if ($category->id === 'custom' && $category->name === 'Custom Category') {
                $foundCustom = true;
                break;
            }
        }
        if (!$foundCustom) {
            throw new Exception('error on testGetCategories - custom category not found after insertion');
        }
    }

    public function testGetTypes(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $types = $this->service->getTypes();
        if (count($types) === 0) {
            throw new Exception('error on testGetTypes - should not be empty');
        }
        $foundServer = false;
        foreach ($types as $type) {
            if ($type->id === 'server') {
                $foundServer = true;
                break;
            }
        }
        if (!$foundServer) {
            throw new Exception('error on testGetTypes - server type not found');
        }

        $this->service->insertType(new ModelType('customType', 'Custom Type'));
        $types = $this->service->getTypes();
        $foundCustomType = false;
        foreach ($types as $type) {
            if ($type->id === 'customType' && $type->name === 'Custom Type') {
                $foundCustomType = true;
                break;
            }
        }
        if (!$foundCustomType) {
            throw new Exception('error on testGetTypes - custom type not found after insertion');
        }
    }
    
    public function testGetGraph(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('n1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('n2', 'Node 02', 'business', 'server', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge1 = new ModelEdge('n1', 'n2', ['weight' => '10']);
        $this->service->insertEdge($edge1);
        $graph = $this->service->getGraph();
        if (count($graph->getNodes()) !== 2) {
            throw new Exception('error on testGetGraph - expected 2 nodes');
        }
        if (count($graph->getEdges()) !== 1) {
            throw new Exception('error on testGetGraph - expected 1 edge');
        }
    }
    
    public function testGetNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = $this->service->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on testGetNode - should be null');
        }
        $newNode = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($newNode);
        $node = $this->service->getNode('node1');
        if ($node->getId() !== 'node1' || $node->getLabel() !== 'Node 01' || $node->getCategory() !== 'business' || $node->getType() !== 'server') {
            throw new Exception('error on testGetNode');
        }
        $data = $node->getData();
        if ($data['key'] !== 'value') {
            throw new Exception('error on testGetNode - data mismatch');
        }
    }
    
    public function testGetNodes(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $nodes = $this->service->getNodes();
        if (count($nodes) !== 0) {
            throw new Exception('error on testGetNodes - should be empty');
        }
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'business', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $nodes = $this->service->getNodes();
        if (count($nodes) !== 2) {
            throw new Exception('error on testGetNodes - expected 2 nodes');
        }
        if ($nodes[0]->getId() !== 'node1' || $nodes[0]->getLabel() !== 'Node 01') {
            throw new Exception('error on testGetNodes - first node mismatch');
        }
        if ($nodes[1]->getId() !== 'node2' || $nodes[1]->getLabel() !== 'Node 02') {
            throw new Exception('error on testGetNodes - second node mismatch');
        }
    }

    public function testGetNodeParentOf(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $nodeA = new ModelNode('nodeA', 'Node A', 'business', 'server', ['key' => 'valueA']);
        $nodeB = new ModelNode('nodeB', 'Node B', 'business', 'database', ['key' => 'valueB']);
        $nodeC = new ModelNode('nodeC', 'Node C', 'business', 'server', ['key' => 'valueC']);
        $this->service->insertNode($nodeA);
        $this->service->insertNode($nodeB);
        $this->service->insertNode($nodeC);
        $edgeAB = new ModelEdge('nodeA', 'nodeB', ['relation' => 'parent']);
        $edgeAC = new ModelEdge('nodeA', 'nodeC', ['relation' => 'parent']);
        $this->service->insertEdge($edgeAB);
        $this->service->insertEdge($edgeAC);
        $parentOfB = $this->service->getNodeParentOf('nodeB');
        
        if ($parentOfB === null || $parentOfB->getId() !== 'nodeA' || $parentOfB->getLabel() !== 'Node A') {
            throw new Exception('error on testGetNodeParentOf - parent of nodeB should be nodeA');
        }

        $parentOfA = $this->service->getNodeParentOf('nodeA');
        if ($parentOfA !== null) {
            throw new Exception('error on testGetNodeParentOf - nodeA should have no parent');
        }
    }

    public function testGetDependentNodesOf(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $nodeA = new ModelNode('nodeA', 'Node A', 'business', 'server', ['key' => 'valueA']);
        $nodeB = new ModelNode('nodeB', 'Node B', 'business', 'database', ['key' => 'valueB']);
        $nodeC = new ModelNode('nodeC', 'Node C', 'business', 'server', ['key' => 'valueC']);
        $this->service->insertNode($nodeA);
        $this->service->insertNode($nodeB);
        $this->service->insertNode($nodeC);
        $edgeAB = new ModelEdge('nodeA', 'nodeB', ['relation' => 'parent']);
        $edgeAC = new ModelEdge('nodeA', 'nodeC', ['relation' => 'parent']);
        $this->service->insertEdge($edgeAB);
        $this->service->insertEdge($edgeAC);
        $dependentsOfA = $this->service->getDependentNodesOf('nodeA');
        if (count($dependentsOfA) !== 2) {
            throw new Exception('error on testGetDependentNodesOf - expected 2 dependent nodes for nodeA');
        }
        $ids = array_map(fn($node) => $node->getId(), $dependentsOfA);
        if (!in_array('nodeB', $ids) || !in_array('nodeC', $ids)) {
            throw new Exception('error on testGetDependentNodesOf - dependent nodes mismatch for nodeA');
        }
    }
    
    public function testInsertNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($node);
        $retrievedNode = $this->service->getNode('node1');
        if ($retrievedNode->getId() !== 'node1' || $retrievedNode->getLabel() !== 'Node 01') {
            throw new Exception('error on testInsertNode');
        }
        // Test with contributor permission
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node2);
        $retrievedNode2 = $this->service->getNode('node2');
        if ($retrievedNode2->getId() !== 'node2') {
            throw new Exception('error on testInsertNode - contributor should be able to insert');
        }
    }
    
    public function testUpdateNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($node);
        $updatedNode = new ModelNode('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        
        $this->service->updateNode($updatedNode);
        $retrievedNode = $this->service->getNode('node1');

        
        if ($retrievedNode->getLabel() !== 'Updated Node' || $retrievedNode->getCategory() !== 'application' || $retrievedNode->getType() !== 'database') {
            throw new Exception('error on testUpdateNode compare label and category');
        }
        $data = $retrievedNode->getData();
        if ($data['key'] !== 'newvalue') {
            throw new Exception('error on testUpdateNode - data not updated');
        }
        
        // try to update node not found
        $updatedNode = new ModelNode('node5', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        if ($this->service->updateNode($updatedNode)) {
            throw new Exception('error on testUpdateNode - should be false');
        }
    }
    
    public function testDeleteNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $this->service->deleteNode(new ModelNode('id', 'one node', 'application', 'database', []));

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);

        $node = $this->service->getNode('node1');
        if (is_null($node)) {
            throw new Exception('error on testDeleteNode');
        }

        $this->service->deleteNode($node1);

        $node = $this->service->getNode('node1');
        if (! is_null($node)) {
            throw new Exception('error on testDeleteNode');
        }
    }
    
    public function testGetEdge(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = $this->service->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge - should be null');
        }
        $newEdge = new ModelEdge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($newEdge);
        $edge = $this->service->getEdge('node1', 'node2');
        if ($edge->getId() !== 'node1-node2' || $edge->getSource() !== 'node1' || $edge->getTarget() !== 'node2') {
            throw new Exception('error on testGetEdge');
        }
        $data = $edge->getData();
        if ($data['weight'] !== '10') {
            throw new Exception('error on testGetEdge - data mismatch');
        }
    }
    
    public function testGetEdges(): void {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $edges = $this->service->getEdges();
        if (count($edges) !== 0) {
            throw new Exception('error on testGetEdges - should be empty');
        }
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new ModelNode('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);
        $edge1 = new ModelEdge('node1', 'node2', ['weight' => '10']);
        $edge2 = new ModelEdge('node2', 'node3', ['weight' => '20']);
        $this->service->insertEdge($edge1);
        $this->service->insertEdge($edge2);
        $edges = $this->service->getEdges();
        if (count($edges) !== 2) {
            throw new Exception('error on testGetEdges - expected 2 edges');
        }
        if ($edges[0]->getId() !== 'node1-node2' || $edges[0]->getSource() !== 'node1') {
            throw new Exception('error on testGetEdges - first edge mismatch');
        }
        if ($edges[1]->getId() !== 'node2-node3' || $edges[1]->getSource() !== 'node2') {
            throw new Exception('error on testGetEdges - second edge mismatch');
        }
    }
    
    public function testInsertEdge(): void {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new ModelEdge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge->getId() !== 'node1-node2' || $retrievedEdge->getSource() !== 'node1' || $retrievedEdge->getTarget() !== 'node2') {
            throw new Exception('error on testInsertEdge');
        }
    }
    
    public function testUpdateEdge(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new ModelNode('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);

        $edge = new ModelEdge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        
        $updatedEdge = new ModelEdge('node1', 'node2', ['weight' => '30']);
        $this->service->updateEdge($updatedEdge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');

        if ($retrievedEdge->getSource() !== 'node1' || $retrievedEdge->getTarget() !== 'node2') {
            throw new Exception('error on testUpdateEdge');
        }
        $data = $retrievedEdge->getData();
        if ($data['weight'] !== '30') {
            throw new Exception('error on testUpdateEdge - data not updated');
        }

        // Test updating non-existent edge
        $nonExistentEdge = new ModelEdge('node1', 'node3', ['weight' => '50']);
        if ($this->service->updateEdge($nonExistentEdge)) {
            throw new Exception('error on testUpdateEdge - should return false for non-existent edge');
        }
    }
    
    public function testDeleteEdge(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new ModelEdge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge === null) {
            throw new Exception('error on testDeleteEdge - edge not inserted');
        }
        $this->service->deleteEdge(new ModelEdge('node1', 'node2'));
        $edges = $this->service->getEdges();
        if (count($edges) !== 0) {
            throw new Exception('error on testDeleteEdge - edge not deleted');
        }

        // Test deleting non-existent edge
        if ($this->service->deleteEdge(new ModelEdge('node1', 'node2'))) {
            throw new Exception('error on testDeleteEdge - should return false for non-existent edge');
        }
    }
    
    public function testGetStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $statuses = $this->service->getStatus();

        if (count($statuses) !== 0) {
            throw new Exception('error on testGetStatus - should be empty');
        }
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->updateNodeStatus(new ModelStatus('node1', 'healthy'));
        $this->service->updateNodeStatus(new ModelStatus('node2', 'unhealthy'));
        $statuses = $this->service->getStatus();
        if (count($statuses) !== 2) {
            throw new Exception('error on testGetStatus - expected 2 statuses');
        }
    }
    
    public function testGetNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'unknown') {
            throw new Exception('error on testGetNodeStatus - default should be unknown');
        }
        $this->service->updateNodeStatus(new ModelStatus('node1', 'healthy'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'healthy') {
            throw new Exception('error on testGetNodeStatus - status should be healthy');
        }
    }
    
    public function testUpdateNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        $this->service->updateNodeStatus(new ModelStatus('node1', 'healthy'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getStatus() !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus - status not set');
        }
        $this->service->updateNodeStatus(new ModelStatus('node1', 'maintenance'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getStatus() !== 'maintenance') {
            throw new Exception('error on testUpdateNodeStatus - status not updated');
        }

        try {
            $this->service->updateNodeStatus(new ModelStatus('node6', 'healthy'));
        } catch (PDOException $e) {
            return;
        }
        throw new Exception('error on testUpdateNodeStatus');
    }
    
    public function testGetLogs(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $logs = $this->service->getLogs(10);
        if (count($logs) !== 0) {
            throw new Exception('error on testGetLogs - should be empty');
        }

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        sleep(1);
        $updatedNode = new ModelNode('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $this->service->updateNode($updatedNode);
        sleep(1);
        $this->service->deleteNode($node1);
        sleep(1);
        $logs = $this->service->getLogs(10);
        if (count($logs) !== 3) {
            throw new Exception('error on testGetLogs - expected 3 log entries (insert, update, delete)');
        }
        if ($logs[0]->action !== 'delete' || $logs[0]->entityType !== 'node') {
            throw new Exception('error on testGetLogs - first log should be delete');
        }
        if ($logs[1]->action !== 'update' || $logs[1]->entityType !== 'node') {
            throw new Exception('error on testGetLogs - second log should be update');
        }
        if ($logs[2]->action !== 'insert' || $logs[2]->entityType !== 'node') {
            throw new Exception('error on testGetLogs - third log should be insert');
        }
    }
}
