<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';
require_once dirname(__DIR__) . '/src/GraphDatabase.php';
require_once dirname(__DIR__) . '/src/Logger.php';

class TestGraphDatabase extends AbstractTest
{
    private ?PDO $pdo;
    private ?LoggerInterface $logger;
    private ?GraphDatabase $graphDB;

    public function up(): void
    {
        $this->pdo = GraphDatabase::createConnection('sqlite::memory:');
        $this->logger = new Logger('database.log');
        $this->graphDB = new GraphDatabase($this->pdo, $this->logger);
    }

    public function down(): void
    {
        $this->pdo = null;
        $this->logger = null;
        $this->graphDB = null;
    }

    public function testGetUser(): void
    {
        $user = $this->graphDB->getUser('maria');
        if ($user !== null) {
            throw new Exception('deveria retornar null');
        }

        $user = $this->graphDB->getUser('admin');
        if($user['id'] !== 'admin' || $user['user_group'] !== 'admin') {
            throw new Exception('admin expected');
        }
    }

    public function testInsertUser(): void
    {
        $this->graphDB->insertUser('maria', 'contributor');
        $user = $this->graphDB->getUser('maria');
        if($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
            throw new Exception('maria expected');
        }
    }

    public function testUpdateUser(): void {
        $this->graphDB->insertUser('maria', 'contributor');
        $this->graphDB->updateUser('maria', 'admin');
        $user = $this->graphDB->getUser('maria');
        if($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
            throw new Exception('expected maria admin');
        }
    }

    public function testGetNode(): void {
        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $node = $this->graphDB->getNode('node1');
        if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
            throw new Exception('error on getNode');
        }

        if ($node['data']['running_on'] != 'SRV01OP') {
            throw new Exception('error on getNode');
        }
    }

    public function testGetNodes(): void {
        $this->graphDB->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);
        $nodes = $this->graphDB->getNodes();

        if(count($nodes) != 2) {
            throw new Exception('error on test_getNodes');
        }

        if($nodes[0]['id'] != 'node1' || $nodes[0]['label'] != 'Node 01' || $nodes[0]['category'] != 'application' || $nodes[0]['type'] != 'application') {
            throw new Exception('error on getNode');
        }

        if ($nodes[0]['data']['running_on'] != 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if($nodes[1]['id'] != 'node2' || $nodes[1]['label'] != 'Node 02' || $nodes[1]['category'] != 'business' || $nodes[1]['type'] != 'database') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]['data']['running_on'] != 'SRV011P') {
            throw new Exception('error on getNode');
        }
    }

    public function testInsertNode(): void {
        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $node = $this->graphDB->getNode('node1');
        if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
            throw new Exception('error on getNode');
        }

        if ($node['data']['running_on'] != 'SRV01OP') {
            throw new Exception('error on getNode');
        }
    }

    public function testUpdateNode(): void {
        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $this->graphDB->updateNode('node1', 'Novo Label', 'application', 'database', ['other' => 'diff']);
        $node = $this->graphDB->getNode('node1');
        if($node['id'] != 'node1' || $node['label'] != 'Novo Label' || $node['category'] != 'application' || $node['type'] != 'database') {
            throw new Exception('error on test_updateNode');
        }

        if ($node['data']['other'] != 'diff') {
            throw new Exception('error on test_updateNode');
        }
    }

    public function testDeleteNode(): void {
        $node = $this->graphDB->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on test_deleteNode');
        }
        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $node = $this->graphDB->getNode('node1');
        if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
            throw new Exception('error on test_updateNode');
        }

        $this->graphDB->deleteNode('node1');
        $node = $this->graphDB->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on test_deleteNode');
        }
    }

    public function testGetEdge(): void {
        $edge = $this->graphDB->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_getEdge');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

        $edge = $this->graphDB->getEdge('node1', 'node2');

        if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
            throw new Exception('error on test_Database_getEdge');
        }

        if ($edge['data']['a'] != 'b') {
            throw new Exception('error on test_Database_getEdge');
        }
    }

    public function testGetEdgeById(): void {
        $edge = $this->graphDB->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_Database_getEdge');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

        $edge = $this->graphDB->getEdgeById('edge1');

        if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
            throw new Exception('error on test_Database_getEdge');
        }

        if ($edge['data']['a'] != 'b') {
            throw new Exception('error on test_Database_getEdge');
        }

        $edge = $this->graphDB->getEdgeById('edge2');
        if ($edge !== null) {
            throw new Exception('error on test_Database_getEdge');
        }
    }

    public function testGetEdges(): void {
        $edge = $this->graphDB->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_getEdges');
        }

        $edge = $this->graphDB->getEdge('node2', 'node3');
        if ($edge !== null) {
            throw new Exception('error on test_getEdges');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->graphDB->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);

        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
        $this->graphDB->insertEdge('edge2', 'node2', 'node3', ['b' => 'c']);

        $edges = $this->graphDB->getEdges();
        if(count($edges) != 2) {
            throw new Exception('error on test_getEdges');
        }

        if($edges[0]['id'] != 'edge1' || $edges[0]['source'] != 'node1' || $edges[0]['target'] != 'node2' || $edges[0]['data']['a'] != 'b') {
            throw new Exception('error on test_getEdges');
        }

        if($edges[1]['id'] != 'edge2' || $edges[1]['source'] != 'node2' || $edges[1]['target'] != 'node3' || $edges[1]['data']['b'] != 'c') {
            throw new Exception('error on test_getEdges');
        }
    }

    public function testInsertEdge(): void {
        $edge = $this->graphDB->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_Database_insertEdge');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

        $edge = $this->graphDB->getEdge('node1', 'node2');

        if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
            throw new Exception('error on test_Database_insertEdge');
        }

        if ($edge['data']['a'] != 'b') {
            throw new Exception('error on test_Database_insertEdge');
        }

        $this->graphDB->insertEdge('edge2', 'node2', 'node1', ['a' => 'b']);
        $edge = $this->graphDB->getEdge('node2', 'node1');

        if ($edge !== null) {
            throw new Exception('error on test_Database_insertEdge');
        }
    }

    public function testUpdateEdge(): void {
        $edge = $this->graphDB->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on test_updateEdge');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->graphDB->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);
        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

        $this->graphDB->updateEdge('edge1', 'node2', 'node3', ['x' => 'y']);

        $edge = $this->graphDB->getEdgeById('edge1');

        if($edge['id'] != 'edge1' || $edge['source'] != 'node2' || $edge['target'] != 'node3') {
            throw new Exception('error on test_updateEdge');
        }

        if ($edge['data']['x'] != 'y') {
            throw new Exception('error on test_updateEdge');
        }
    }

    public function testDeleteEdge(): void {
        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        $this->graphDB->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->graphDB->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);
        $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
        $this->graphDB->insertEdge('edge2', 'node2', 'node3', ['b' => 'c']);

        if(count($this->graphDB->getEdges()) != 2) {
            throw new Exception('error on test_deleteEdge');
        }

        $this->graphDB->deleteEdge('edge1');
        $this->graphDB->deleteEdge('edge2');

        if(count($this->graphDB->getEdges()) != 0) {
            throw new Exception('error on test_deleteEdge');
        }
    }

    public function testGetStatus(): void {
        $s = $this->graphDB->getStatus();

        if (count($s) != 0) {
            throw new Exception('error on test_getStatuses');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

        $s = $this->graphDB->getStatus();

        if (count($s) != 1) {
            throw new Exception('error on test_getStatuses');
        }

        if ($s[0]['id'] != 'node1' || $s[0]['status'] !== null) {
            throw new Exception('error on test_getStatuses');
        }
    }

    public function testGetNodeStatus(): void {
        $s = $this->graphDB->getStatus();

        if (count($s) != 0) {
            throw new Exception('error on test_getStatuses');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

        $s = $this->graphDB->getNodeStatus('node1');

        if ($s['id'] != 'node1' || $s['status'] !== null) {
            throw new Exception('error on test_getStatuses');
        }
    }

    public function testUpdateNodeStatus(): void {
        $s = $this->graphDB->getStatus();

        if (count($s) != 0) {
            throw new Exception('error on test_updateNodeStatus');
        }

        $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

        $this->graphDB->updateNodeStatus('node1', 'healthy');

        $s = $this->graphDB->getNodeStatus('node1');

        if ($s['id'] != 'node1' || $s['status'] !== 'healthy') {
            throw new Exception('error on test_updateNodeStatus');
        }
    }

    public function testGetLogs(): void {
        $logs = $this->graphDB->getLogs(2);
        if(count($logs) > 0) {
            throw new Exception('problem on test_Database_getLogs');
        }

        $this->graphDB->insertLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
        sleep(1);
        $this->graphDB->insertLog('node', 'node2', 'update', null, null, 'admin', '127.0.0.1');

        $logs = $this->graphDB->getLogs(2);
        if(count($logs) != 2) {
            throw new Exception('problem on test_Database_getLogs');
        }

        if ($logs[0]['entity_id'] != 'node2') {
            throw new Exception('problem on test_Database_getLogs');
        }

        if ($logs[1]['entity_id'] != 'node1') {
            throw new Exception('problem on test_Database_getLogs');
        }
    }

    public function testInsertAuditLog(): void {
        $this->graphDB->insertLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
        $logs = $this->graphDB->getLogs(2);
        if(count($logs) != 1) {
            throw new Exception('problem on test_Database_getLogs');
        }

        if ($logs[0]['entity_id'] != 'node1') {
            throw new Exception('problem on test_Database_getLogs');
        }
    }
}

// function test_Database_insertUser_Exception(): void {
//     $pdo->exec('DROP TABLE users');
//     try {
//         $this->graphDB->insertUser('maria', 'contributor');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_insertUser_Exception');
// }

// function test_Database_updateUser_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE users');
//     try {
//         $this->graphDB->updateUser('maria', 'contributor');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_updateUser_Exception');
// }

// function test_Database_getNode_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->graphDB->getNode('node1');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getNode_Exception');
// }

// function test_Database_getNodes_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->graphDB->getNodes();
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getNodes_Exception');
// }

// function test_Database_insertNode_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->graphDB->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_insertNode_Exception');
// }

// function test_Database_updateNode_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->graphDB->updateNode('node1', 'Novo Label', 'application', 'database', ['other' => 'diff']);
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_updateNode_Exception');
// }

// function test_Database_deleteNode_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE nodes');
//     try {
//         $this->graphDB->deleteNode('node1');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_deleteNode_Exception');
// }

// function test_Database_getEdge_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->getEdge('node1', 'node2');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getEdge_Exception');
// }

// function test_Database_getEdgeById_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->getEdgeById('edge1');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getEdgeById_Exception');
// }

// function test_Database_getEdges_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->getEdges();
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getEdges_Exception');
// }

// function test_Database_insertEdge_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_insertEdge_Exception');
// }

// function test_Database_updateEdge_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->updateEdge('edge1', 'node2', 'node3', ['x' => 'y']);
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_updateEdge_Exception');
// }

// function test_Database_deleteEdge_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE edges');
//     try {
//         $this->graphDB->deleteEdge('edge1');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_deleteEdge_Exception');
// }

// function test_Database_getStatuses_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE status');
//     try {
//         $this->graphDB->getStatuses();
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getStatuses_Exception');
// }

// function test_Database_getNodeStatus_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE status');
//     try {
//         $this->graphDB->getNodeStatus('node1');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getNodeStatus_Exception');
// }

// function test_Database_updateNodeStatus_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE status');
//     try {
//         $this->graphDB->updateNodeStatus('node1', 'healthy');
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_updateNodeStatus_Exception');
// }

// function test_Database_getLogs_Exception(): void
// {
//     [$this->graphDB, $pdo] = createConnection();
//     $pdo->exec('DROP TABLE audit');
//     try {
//         $this->graphDB->getLogs(2);
//         return;
//     } catch(DatabaseException $e) {
//         return;
//     }
//     throw new Exception('other exception in test_Database_getLogs_Exception');
// }
