<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';
require_once dirname(__DIR__) . '/src/GraphService.php';

class TestGraphService extends AbstractTest
{
    private ?PDO $pdo;

    private ?logger $databaseLogger;
    private ?logger $serviceLogger;

    private ?GraphDatabaseInterface $graphDB;
    private ?GraphServiceInterface $service;

    public function up(): void
    {
        $this->pdo = GraphDatabase::createConnection('sqlite::memory:');
        
        $this->databaseLogger = new Logger('database.log');
        $this->graphDB = new GraphDatabase($this->pdo, $this->databaseLogger);

        $this->serviceLogger = new Logger('service.log');
        $this->service = new GraphService($this->graphDB, $this->serviceLogger);
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
        $user = $this->service->getUser('maria');
        if ($user !== null) {
            throw new Exception('error on test_Service_getUser');
        }

        $user = $this->service->getUser('admin');
        
        if($user->getId() != 'admin' || $user->getGroup()->getId() != 'admin') {
            throw new Exception('error on test_Service_getUser');
        }
    }
    
    public function testInsertUser(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');

        $user = $this->service->getUser('maria');
        if ($user !== null) {
            throw new Exception('error on test_Service_getUser');
        }
        $this->service->insertUser(new User('maria', new Group('contributor')));
    }
    
    public function testUpdateUser(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $this->service->insertUser(new User('maria', new Group('contributor')));
        $this->service->updateUser(new User('maria', new Group('admin')));
        $user = $this->service->getUser('maria');
        if($user->getId() != 'maria' || $user->getGroup()->getId() != 'admin') {
            throw new Exception('error on test_Service_updateUser');
        }
    }
    
    public function testGetGraph(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('n1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('n2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        
        $edge1 = new Edge('n1', 'n2', ['weight' => '10']);
        $this->service->insertEdge($edge1);
        
        $graph = $this->service->getGraph();

        if (count($graph->getNodes()) != 2) {
            throw new Exception('error on test_Service_getGraph - expected 2 nodes');
        }
        
        if (count($graph->getEdges()) != 1) {
            throw new Exception('error on test_Service_getGraph - expected 1 edge');
        }
    }
    
    public function testGetNode(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node = $this->service->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on test_Service_getNode - should be null');
        }
        $newNode = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($newNode);
        $node = $this->service->getNode('node1');
        if ($node->getId() != 'node1' || $node->getLabel() != 'Node 01' || $node->getCategory() != 'business' || $node->getType() != 'server') {
            throw new Exception('error on test_Service_getNode');
        }
        $data = $node->getData();
        if ($data['key'] != 'value') {
            throw new Exception('error on test_Service_getNode - data mismatch');
        }
    }
    
    public function testGetNodes(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $nodes = $this->service->getNodes();
        if (count($nodes) != 0) {
            throw new Exception('error on test_Service_getNodes - should be empty');
        }
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $nodes = $this->service->getNodes();
        if (count($nodes) != 2) {
            throw new Exception('error on test_Service_getNodes - expected 2 nodes');
        }
        if ($nodes[0]->getId() != 'node1' || $nodes[0]->getLabel() != 'Node 01') {
            throw new Exception('error on test_Service_getNodes - first node mismatch');
        }
        if ($nodes[1]->getId() != 'node2' || $nodes[1]->getLabel() != 'Node 02') {
            throw new Exception('error on test_Service_getNodes - second node mismatch');
        }
    }
    
    public function testInsertNode(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($node);
        $retrievedNode = $this->service->getNode('node1');
        if ($retrievedNode->getId() != 'node1' || $retrievedNode->getLabel() != 'Node 01') {
            throw new Exception('error on test_Service_insertNode');
        }
        // Test with contributor permission
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node2);
        $retrievedNode2 = $this->service->getNode('node2');
        if ($retrievedNode2->getId() != 'node2') {
            throw new Exception('error on test_Service_insertNode - contributor should be able to insert');
        }
    }
    
    public function testUpdateNode(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $this->service->insertNode($node);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $this->service->updateNode($updatedNode);
        $retrievedNode = $this->service->getNode('node1');
        if ($retrievedNode->getLabel() != 'Updated Node' || $retrievedNode->getCategory() != 'application') {
            throw new Exception('error on test_Service_updateNode');
        }
        $data = $retrievedNode->getData();
        if ($data['key'] != 'newvalue') {
            throw new Exception('error on test_Service_updateNode - data not updated');
        }
        // try to update node not found
        $updatedNode = new Node('node5', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $exists = $this->service->updateNode($updatedNode);
        if(!is_null($exists)) {
            throw new Exception('error on test_Service_updateNode');
        }
    }
    
    public function testDeleteNode(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');

        $this->service->deleteNode('id');

        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);

        $node = $this->service->getNode('node1');
        if(is_null($node)) {
            throw new Exception('problem on testDeleteNode');
        }

        $this->service->deleteNode('node1');

        $node = $this->service->getNode('node1');
        if(! is_null($node)) {
            throw new Exception('problem on testDeleteNode');
        }
    }
    
    public function testGetEdge(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = $this->service->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_Service_getEdge - should be null');
        }
        $newEdge = new Edge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($newEdge);
        $edge = $this->service->getEdge('node1', 'node2');
        if ($edge->getId() != 'node1-node2' || $edge->getSource() != 'node1' || $edge->getTarget() != 'node2') {
            throw new Exception('error on test_Service_getEdge');
        }
        $data = $edge->getData();
        if ($data['weight'] != '10') {
            throw new Exception('error on test_Service_getEdge - data mismatch');
        }
    }
    
    public function testGetEdges(): void {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $edges = $this->service->getEdges();
        if (count($edges) != 0) {
            throw new Exception('error on test_Service_getEdges - should be empty');
        }
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);
        $edge1 = new Edge('node1', 'node2', ['weight' => '10']);
        $edge2 = new Edge('node2', 'node3', ['weight' => '20']);
        $this->service->insertEdge($edge1);
        $this->service->insertEdge($edge2);
        $edges = $this->service->getEdges();
        if (count($edges) != 2) {
            throw new Exception('error on test_Service_getEdges - expected 2 edges');
        }
        if ($edges[0]->getId() != 'node1-node2' || $edges[0]->getSource() != 'node1') {
            throw new Exception('error on test_Service_getEdges - first edge mismatch');
        }
        if ($edges[1]->getId() != 'node2-node3' || $edges[1]->getSource() != 'node2') {
            throw new Exception('error on test_Service_getEdges - second edge mismatch');
        }
    }
    
    public function testInsertEdge(): void {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new Edge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge->getId() != 'node1-node2' || $retrievedEdge->getSource() != 'node1' || $retrievedEdge->getTarget() != 'node2') {
            throw new Exception('error on test_Service_insertEdge');
        }
    }
    
    public function testUpdateEdge(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);
        
        $edge = new Edge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        
        $updatedEdge = new Edge('node1', 'node2', ['weight' => '30']);
        $this->service->updateEdge($updatedEdge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');

        if ($retrievedEdge->getSource() != 'node1' || $retrievedEdge->getTarget() != 'node2') {
            throw new Exception('error on test_Service_updateEdge');
        }
        $data = $retrievedEdge->getData();
        if ($data['weight'] != '30') {
            throw new Exception('error on test_Service_updateEdge - data not updated');
        }
    }
    
    public function testDeleteEdge(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new Edge('node1', 'node2', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge === null) {
            throw new Exception('error on test_Service_deleteEdge - edge not inserted');
        }
        $this->service->deleteEdge(new Edge('node1', 'node2'));
        $edges = $this->service->getEdges();
        if (count($edges) != 0) {
            throw new Exception('error on test_Service_deleteEdge - edge not deleted');
        }
    }
    
    public function testGetStatus(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $statuses = $this->service->getStatus();

        if (count($statuses) != 0) {
            throw new Exception('error on test_Service_getStatuses - should be empty');
        }
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        $this->service->updateNodeStatus(new Status('node2', 'unhealthy'));
        $statuses = $this->service->getStatus();
        if (count($statuses) != 2) {
            throw new Exception('error on test_Service_getStatuses - expected 2 statuses');
        }
    }
    
    public function testGetNodeStatus(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() != 'node1' || $status->getStatus() != 'unknown') {
            throw new Exception('error on test_Service_getNodeStatus - default should be unknown');
        }
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() != 'node1' || $status->getStatus() != 'healthy') {
            throw new Exception('error on test_Service_getNodeStatus - status should be healthy');
        }
    }
    
    public function testUpdateNodeStatus(): void
    {
        GraphContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getStatus() != 'healthy') {
            throw new Exception('error on test_Service_updateNodeStatus - status not set');
        }
        $this->service->updateNodeStatus(new Status('node1', 'maintenance'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getStatus() != 'maintenance') {
            throw new Exception('error on test_Service_updateNodeStatus - status not updated');
        }
    }
    
    public function testGetLogs(): void
    {
        $logs = $this->service->getLogs(10);
        if (count($logs) != 0) {
            throw new Exception('error on test_Service_getLogs - should be empty');
        }

        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $this->service->insertNode($node1);
        sleep(1);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $this->service->updateNode($updatedNode);
        sleep(1);
        $this->service->deleteNode('node1');
        sleep(1);
        $logs = $this->service->getLogs(10);
        if (count($logs) != 3) {
            throw new Exception('error on test_Service_getLogs - expected 3 log entries (insert, update, delete)');
        }
        if ($logs[0]->action != 'delete' || $logs[0]->entityType != 'node') {
            throw new Exception('error on test_Service_getLogs - first log should be delete');
        }
        if ($logs[1]->action != 'update' || $logs[1]->entityType != 'node') {
            throw new Exception('error on test_Service_getLogs - second log should be update');
        }
        if ($logs[2]->action != 'insert' || $logs[2]->entityType != 'node') {
            throw new Exception('error on test_Service_getLogs - third log should be insert');
        }
    }
}

// function test_Service_getUser_Exception(): void
// {
//     $pdo->exec('DROP TABLE users');
//     try {
//         $this->service->getUser('node1');
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_getUser_Exception');
// }

// function test_Service_insertUser_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $user = $this->service->getUser('maria');
//     $pdo->exec('DROP TABLE users');
//     if ($user !== null) {
//         throw new Exception('error on test_Service_getUser');
//     }
//     try {
//         $this->service->insertUser(new User('maria', new Group('contributor')));
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_insertUserException');
// }

// function test_Service_updateUser_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE users');
//     try {
//         $this->service->updateUser(new User('maria', new Group('admin')));
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     $user = $this->service->getUser('maria');
//     if($user->id != 'maria' || $user->group->id != 'admin') {
//         throw new Exception('error on test_Service_updateUser');
//     }
// }

// function test_Service_getGraph_Exception()
// {
//     $pdo->exec('DROP TABLE edges');
//     $pdo->exec('DROP TABLE nodes');
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     try {
//         $this->service->getGraph();
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_getGraph_Exception');
// }

// function test_Service_getNode_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->service->getNode('node1');
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_getNode_Exception');
// }

// function test_Service_insertNode_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
//         $this->service->insertNode($node);
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_insertNode_Exception');
// }

// function test_Service_updateNode_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
//         $this->service->updateNode($updatedNode);
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_updateNode_Exception');
// }


// function test_Service_deleteNode_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->service->deleteNode('node1');
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_deleteNode_Exception');
// }

// function test_Service_getEdge_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->service->getEdge('node1', 'node2');
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_getEdge_Exception');
// }

// function test_Service_getEdges()
// {

// }

// function test_Service_getEdges_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE edges');
//     try{
//         $this->service->getEdges();
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_getEdges_Exception');
// }

// function test_Service_insertEdge_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $edge = new Edge('node1', 'node2', ['weight' => '10']);
//         $this->service->insertEdge($edge);
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('error on test_Service_insertEdge_Exception');
// }

// function test_Service_updateEdge_Exception1()
// {
//     [$this->service, $pdo] = createService();
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     try {
//         $updatedEdge = new Edge('node2', 'node3', ['weight' => '30']);
//         $this->service->updateEdge($updatedEdge);
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_updateEdge_Exception');
// }

// function test_Service_updateEdge_Exception2()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $updatedEdge = new Edge('node2', 'node3', ['weight' => '30']);
//         $this->service->updateEdge($updatedEdge);
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_updateEdge_Exception');
// }


// function test_Service_deleteEdge_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->service->deleteEdge(new Edge('node1', 'node2'));
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_deleteEdge_Exception');
// }

// function test_Service_getStatuses_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE status');
//     try {
//         $this->service->getStatuses();
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_getStatuses_Exception');
// }

// function test_Service_getNodeStatus_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE status');
//     try {
//         $this->service->getNodeStatus('node1');
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_getNodeStatus_Exception');
// }

// function test_Service_updateNodeStatus_Exception()
// {
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     try {
//         $this->service->updateNodeStatus(new Status('node1', 'maintenance'));
//         return;
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_updateNodeStatus_Exception');
// }


// function test_Service_getLogs_Exception()
// {
//     [$this->service, $pdo] = createService();
//     GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
//     $pdo->exec('DROP TABLE audit');
//     try {
//         $this->service->getLogs(10);
//     } catch(GraphServiceException $e) {
//         return;
//     }
//     throw new Exception('exception on test_Service_getLogs_Exception');
// }