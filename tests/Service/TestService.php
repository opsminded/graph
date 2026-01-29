<?php

declare(strict_types=1);

class TestService extends TestAbstractTest
{
    private ?PDO $pdo;

    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;

    public function up(): void
    {
        global $SQL_SCHEMA;
        $this->pdo = Database::createConnection('sqlite::memory:');
        
        $this->databaseLogger = new Logger();
        $this->database = new Database($this->pdo, $this->databaseLogger, $SQL_SCHEMA);
        $this->serviceLogger = new Logger();
        $this->service = new Service($this->database, $this->serviceLogger);

        $this->pdo->exec('delete from audit');
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        $this->pdo->exec('delete from projects');
    }

    public function down(): void
    {
        $this->service = null;
        $this->serviceLogger = null;
        $this->database = null;
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
        $this->service->insertUser(new User('maria', new Group('contributor')));
        
        try {
            $this->service->insertUser(new User('maria', new Group('contributor')));
        } catch (Exception $e) {
            return;
        }
        throw new Exception('error on testInsertUser');
    }
    
    public function testUpdateUser(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $this->service->insertUser(new User('maria', new Group('contributor')));
        $this->service->updateUser(new User('maria', new Group('admin')));
        $user = $this->service->getUser('maria');
        if ($user->getId() !== 'maria' || $user->getGroup()->getId() !== 'admin') {
            throw new Exception('error on testUpdateUser');
        }
        if ($this->service->updateUser(new User('pedro', new Group('admin')))) {
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
            if ($category->getId() === 'business') {
                $foundBusiness = true;
                break;
            }
        }
        if (!$foundBusiness) {
            throw new Exception('error on testGetCategories - business category not found');
        }

        $this->service->insertCategory(new Category('custom', 'Custom Category', 'circle', 100, 100));
        $categories = $this->service->getCategories();
        $foundCustom = false;
        foreach ($categories as $category) {
            if ($category->getId() === 'custom' && $category->getName() === 'Custom Category') {
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
        $foundService = false;
        foreach ($types as $type) {
            if ($type->getId() === 'service') {
                $foundService = true;
                break;
            }
        }
        if (!$foundService) {
            throw new Exception('error on testGetTypes - service type not found');
        }

        $this->service->insertType(new Type('customType', 'Custom Type'));
        $types = $this->service->getTypes();
        $foundCustomType = false;
        foreach ($types as $type) {
            if ($type->getId() === 'customType' && $type->getName() === 'Custom Type') {
                $foundCustomType = true;
                break;
            }
        }
        if (!$foundCustomType) {
            throw new Exception('error on testGetTypes - custom type not found after insertion');
        }
    }
    
    public function testGetNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = $this->service->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on testGetNode - should be null');
        }
        $newNode = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
        $this->service->insertNode($newNode);
        $node = $this->service->getNode('node1');
        if ($node->getId() !== 'node1' || $node->getLabel() !== 'Node 01' || $node->getCategory() !== 'business' || $node->getType() !== 'service') {
            throw new Exception('error on testGetNode');
        }
        $data = $node->getData();
        if ($data['key'] !== 'value') {
            throw new Exception('error on testGetNode - data mismatch');
        }
    }
    
    public function testGetNodes(): void
    {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'business', 'database', ['key' => 'value2']);
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
    
    public function testInsertNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
        $this->service->insertNode($node);
        $retrievedNode = $this->service->getNode('node1');
        if ($retrievedNode->getId() !== 'node1' || $retrievedNode->getLabel() !== 'Node 01') {
            throw new Exception('error on testInsertNode');
        }
        // Test with contributor permission
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node2);
        $retrievedNode2 = $this->service->getNode('node2');
        if ($retrievedNode2->getId() !== 'node2') {
            throw new Exception('error on testInsertNode - contributor should be able to insert');
        }
    }
    
    public function testUpdateNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
        $this->service->insertNode($node);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        
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
        $updatedNode = new Node('node5', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        if ($this->service->updateNode($updatedNode)) {
            throw new Exception('error on testUpdateNode - should be false');
        }
    }
    
    public function testDeleteNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        if ($this->service->deleteNode('id')) {
            throw new Exception('false expected');
        }

        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $this->service->insertNode($node1);

        $node = $this->service->getNode('node1');
        if (is_null($node)) {
            throw new Exception('error on testDeleteNode');
        }

        $this->service->deleteNode($node1->getID());

        $node = $this->service->getNode('node1');
        if (! is_null($node)) {
            throw new Exception('error on testDeleteNode');
        }
    }
    
    public function testGetEdge(): void
    {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $edge  = new Edge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertEdge($edge);
        
        $edge = $this->service->getEdge('node1', 'node3');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge. should be null');
        }
        
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
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        HelperContext::update('admin', 'admin', '127.0.0.1');

        $edges = $this->service->getEdges();
        if (count($edges) !== 0) {
            throw new Exception('error on testGetEdges - should be empty');
        }
        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'application', 'service', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);
        $edge1 = new Edge('node1', 'node2', 'label', ['weight' => '10']);
        $edge2 = new Edge('node2', 'node3', 'label', ['weight' => '20']);
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
        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new Edge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge->getId() !== 'node1-node2' || $retrievedEdge->getSource() !== 'node1' || $retrievedEdge->getTarget() !== 'node2') {
            throw new Exception('error on testInsertEdge');
        }
    }
    
    public function testUpdateEdge(): void
    {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'application', 'service', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);

        $edge = new Edge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);
        
        $updatedEdge = new Edge('node1', 'node2', 'label', ['weight' => '30']);
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
        $nonExistentEdge = new Edge('node1', 'node3', 'label', ['weight' => '50']);
        if ($this->service->updateEdge($nonExistentEdge)) {
            throw new Exception('error on testUpdateEdge - should return false for non-existent edge');
        }
    }
    
    public function testDeleteEdge(): void
    {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        
        $edge = new Edge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);

        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge === null) {
            throw new Exception('error on testDeleteEdge - edge not inserted');
        }

        $this->service->deleteEdge('node1', 'node2');
        $edges = $this->service->getEdges();
        if (count($edges) !== 0) {
            print_r($edges);
            throw new Exception('error on testDeleteEdge - edge not deleted');
        }

        // Test deleting non-existent edge
        if ($this->service->deleteEdge('node1', 'node2')) {
            throw new Exception('error on testDeleteEdge - should return false for non-existent edge');
        }
    }
    
    // public function testGetNodeStatus(): void
    // {
    //     HelperContext::update('admin', 'admin', '127.0.0.1');
    //     $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
    //     $this->service->insertNode($node1);
    //     $status = $this->service->getNodeStatus('node1');
    //     if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'unknown') {
    //         throw new Exception('error on testGetNodeStatus - default should be unknown');
    //     }
    //     $this->service->updateNodeStatus(new Status('node1', 'healthy'));
    //     $status = $this->service->getNodeStatus('node1');
    //     if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'healthy') {
    //         throw new Exception('error on testGetNodeStatus - status should be healthy');
    //     }
    // }
    
    public function testUpdateNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $this->service->insertNode($node1);
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        
        $dbStatus = $this->database->getNodeStatus('node1');
        if ($dbStatus === null) {
            throw new Exception('error on testUpdateNodeStatus - node not found');
        }

        if ($dbStatus->status !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus - status not set');
        }
        $this->service->updateNodeStatus(new Status('node1', 'maintenance'));
        
        $dbStatus = $this->database->getNodeStatus('node1');
        if ($dbStatus === null) {
            throw new Exception('error on testUpdateNodeStatus - node not found');
        }
        
        if ($dbStatus->status !== 'maintenance') {
            throw new Exception('error on testUpdateNodeStatus - status not updated');
        }
        try {
            $this->service->updateNodeStatus(new Status('node6', 'healthy'));
        } catch (DatabaseException $e) {
            return;
        }
        throw new Exception('error on testUpdateNodeStatus');
    }

    public function testGetProject(): void
    {
        $project = $this->service->getProject('nonexistent');
        if( $project !== null) {
            throw new Exception('error on testGetProject - should be null for nonexistent project');
        }


        $this->service->insertProject(
            new Project(
                'project1',
                'First Project',
                'admin',
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            )
        );
        $project = $this->service->getProject('project1');
        if( $project === null || $project->getId() !== 'project1' || $project->getName() !== 'First Project') {
            throw new Exception('error on testGetProject - project data mismatch');
        }
    }

    public function testGetProjects(): void
    {
        $this->pdo->exec('delete from projects');
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $projects = $this->service->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on getProjects - should be empty');
        }

        $this->service->insertProject(
            new Project(
                'project1',
                'First Project',
                'admin',
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            )
        );

        $projects = $this->service->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on getProjects - should have 1 project');
        }
    }

    public function testInsertProject(): void
    {
        $this->pdo->exec('delete from projects');
        
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable()));
        $projects = $this->service->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on testInsertProject - should have 1 project');
        }

        if($projects[0]->getId() !== 'project1' || $projects[0]->getName() !== 'First Project') {
            throw new Exception('error on testInsertProject - project data mismatch');
        }

        try {
            $this->service->insertProject(new Project('project1', 'Duplicate Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable()));
        } catch (Exception $e) {
            return;
        }
        throw new Exception('error on testInsertProject - should not allow duplicate project IDs');
    }

    public function testUpdateProject(): void
    {
        $this->pdo->exec('delete from projects');

        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable()));
        $this->service->updateProject(new Project('project1', 'Updated Project Name', 'admin', new DateTimeImmutable(), new DateTimeImmutable()));
        $projects = $this->service->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on testUpdateProject - should have 1 project');
        }

        if($projects[0]->getName() !== 'Updated Project Name') {
            throw new Exception('error on testUpdateProject - project name not updated');
        }
        
        if($this->service->updateProject(new Project('project2', 'Non-existent Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable()))) {
            throw new Exception('error on testUpdateProject - should return false for non-existent project');
        }
    }

    public function testDeleteProject(): void
    {
        $this->pdo->exec('delete from projects');

        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable()));
        $this->service->deleteProject('project1');
        $projects = $this->service->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on testDeleteProject - should have 0 projects after deletion');
        }
    }
    
    public function testGetLogs(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $logs = $this->service->getLogs(10);
        if (count($logs) !== 0) {
            throw new Exception('error on testGetLogs - should be empty');
        }

        $node1 = new Node('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $this->service->insertNode($node1);
        sleep(1);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $this->service->updateNode($updatedNode);
        sleep(1);
        $this->service->deleteNode($node1->getId());
        sleep(1);
        $logs = $this->service->getLogs(10);
        if (count($logs) !== 3) {
            throw new Exception('error on testGetLogs - expected 3 log entries (insert, update, delete)');
        }
        if ($logs[0]->getAction() !== 'delete' || $logs[0]->getEntityType() !== 'node') {
            throw new Exception('error on testGetLogs - first log should be delete');
        }
        if ($logs[1]->getAction() !== 'update' || $logs[1]->getEntityType() !== 'node') {
            throw new Exception('error on testGetLogs - second log should be update');
        }
        if ($logs[2]->getAction() !== 'insert' || $logs[2]->getEntityType() !== 'node') {
            throw new Exception('error on testGetLogs - third log should be insert');
        }
    }
}
