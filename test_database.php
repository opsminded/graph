<?php

declare(strict_types=1);

require_once __DIR__ . '/graph.php';

ini_set('xdebug.mode', '1');
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

function createConnection(): GraphDatabase
{
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');
    $graphDb = new GraphDatabase($pdo, $databaseLogger);
    return $graphDb;
}

function test_Database_getUser(): void {
    $graphDb = createConnection();

    $user = $graphDb->getUser('maria');
    if ($user !== null) {
        throw new Exception('deveria retornar null');
    }

    $user = $graphDb->getUser('admin');
    if($user['id'] !== 'admin' || $user['user_group'] !== 'admin') {
        throw new Exception('admin expected');
    }
}

function test_Database_insertUser(): void {
    $graphDb = createConnection();
    $graphDb->insertUser('maria', 'contributor');
    $user = $graphDb->getUser('maria');
    if($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
        throw new Exception('maria expected');
    }
}

function test_Database_updateUser(): void {
    $graphDb = createConnection();
    $graphDb->insertUser('maria', 'contributor');
    $graphDb->updateUser('maria', 'admin');
    $user = $graphDb->getUser('maria');
    if($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
        throw new Exception('expected maria admin');
    }
}

function test_Database_getNode(): void {
    $graphDb = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'service') {
        throw new Exception('error on getNode');
    }

    if ($node['data']['running_on'] != 'SRV01OP') {
        throw new Exception('error on getNode');
    }
}

function test_Database_getNodes(): void {
    $graphDb = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);
    $nodes = $graphDb->getNodes();

    if(count($nodes) != 2) {
        throw new Exception('error on test_getNodes');
    }

    if($nodes[0]['id'] != 'node1' || $nodes[0]['label'] != 'Node 01' || $nodes[0]['category'] != 'application' || $nodes[0]['type'] != 'service') {
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

function test_Database_insertNode(): void {
    $graphDb = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'service') {
        throw new Exception('error on getNode');
    }

    if ($node['data']['running_on'] != 'SRV01OP') {
        throw new Exception('error on getNode');
    }
}

function test_Database_updateNode(): void {
    $graphDb = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
    $graphDb->updateNode('node1', 'Novo Label', 'application', 'outro', ['other' => 'diff']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Novo Label' || $node['category'] != 'application' || $node['type'] != 'outro') {
        throw new Exception('error on test_updateNode');
    }

    if ($node['data']['other'] != 'diff') {
        throw new Exception('error on test_updateNode');
    }
}

function test_Database_deleteNode(): void {
    $graphDb = createConnection();
    $node = $graphDb->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_deleteNode');
    }
    $graphDb->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'service') {
        throw new Exception('error on test_updateNode');
    }

    $graphDb->deleteNode('node1');
    $node = $graphDb->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_deleteNode');
    }
}

function test_Database_getEdge(): void {
    $graphDb = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_getEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

    $edge = $graphDb->getEdge('node1', 'node2');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
        throw new Exception('error on test_Database_getEdge');
    }

    if ($edge['data']['a'] != 'b') {
        throw new Exception('error on test_Database_getEdge');
    }
}

function test_Database_getEdgeById(): void {
    $graphDb = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_Database_getEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

    $edge = $graphDb->getEdgeById('edge1');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
        throw new Exception('error on test_Database_getEdge');
    }

    if ($edge['data']['a'] != 'b') {
        throw new Exception('error on test_Database_getEdge');
    }
}

function test_Database_getEdges(): void {
    $graphDb = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_getEdges');
    }

    $edge = $graphDb->getEdge('node2', 'node3');
    if ($edge !== null) {
        throw new Exception('error on test_getEdges');
    }

    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'categ2', 'type2', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'categ3', 'type3', ['running_on' => 'SRV012P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
    $graphDb->insertEdge('edge2', 'node2', 'node3', ['b' => 'c']);

    $edges = $graphDb->getEdges();
    if(count($edges) != 2) {
        throw new Exception('error on test_getEdges');
    }

    if($edges[0]['id'] != 'edge1' || $edges[0]['source'] != 'node1' || $edges[0]['target'] != 'node2' || $edges[0]['data']['a'] != 'b') {
        throw new Exception('error on test_getEdges');
    }

    if($edges[1]['id'] != 'edge2' || $edges[1]['source'] != 'node2' || $edges[1]['target'] != 'node3' || $edges[1]['data']['b'] != 'c') {
        print_r($edges[1]);
        throw new Exception('error on test_getEdges');
    }
}

function test_Database_insertEdge(): void {
    $graphDb = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_getEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

    $edge = $graphDb->getEdge('node1', 'node2');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
        throw new Exception('error on test_getEdge');
    }

    if ($edge['data']['a'] != 'b') {
        throw new Exception('error on test_getEdge');
    }
}

function test_Database_updateEdge(): void {
    $graphDb = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_updateEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'categ2', 'type2', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'categ3', 'type3', ['running_on' => 'SRV012P']);
    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
    
    $graphDb->updateEdge('edge1', 'node2', 'node3', ['x' => 'y']);

    $edge = $graphDb->getEdgeById('edge1');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node2' || $edge['target'] != 'node3') {
        throw new Exception('error on test_updateEdge');
    }

    if ($edge['data']['x'] != 'y') {
        throw new Exception('error on test_updateEdge');
    }
}

function test_Database_deleteEdge(): void {
    $graphDb = createConnection();
    
    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'categ2', 'type2', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'categ3', 'type3', ['running_on' => 'SRV012P']);
    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
    $graphDb->insertEdge('edge2', 'node2', 'node3', ['b' => 'c']);

    if(count($graphDb->getEdges()) != 2) {
        throw new Exception('error on test_deleteEdge');
    }

    $graphDb->deleteEdge('edge1');
    $graphDb->deleteEdge('edge2');

    if(count($graphDb->getEdges()) != 0) {
        throw new Exception('error on test_deleteEdge');
    }
}

function test_Database_getStatuses(): void {
    $graphDb = createConnection();
    $s = $graphDb->getStatuses();
    
    if (count($s) != 0) {
        throw new Exception('error on test_getStatuses');
    }

    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);

    $s = $graphDb->getStatuses();
    
    if (count($s) != 1) {
        throw new Exception('error on test_getStatuses');
    }

    if ($s[0]['id'] != 'node1' || $s[0]['status'] !== null) {
        print_r($s);
        throw new Exception('error on test_getStatuses');
    }
}

function test_Database_getNodeStatus(): void {
    $graphDb = createConnection();
    $s = $graphDb->getStatuses();
    
    if (count($s) != 0) {
        throw new Exception('error on test_getStatuses');
    }

    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);

    $s = $graphDb->getNodeStatus('node1');
    
    if ($s['id'] != 'node1' || $s['status'] !== null) {
        print_r($s);
        throw new Exception('error on test_getStatuses');
    }
}

function test_Database_updateNodeStatus(): void {
    $graphDb = createConnection();
    $s = $graphDb->getStatuses();
    
    if (count($s) != 0) {
        throw new Exception('error on test_updateNodeStatus');
    }

    $graphDb->insertNode('node1', 'Node 01', 'categ1', 'type1', ['running_on' => 'SRV01OP']);

    $graphDb->updateNodeStatus('node1', 'healthy');

    $s = $graphDb->getNodeStatus('node1');
    
    if ($s['id'] != 'node1' || $s['status'] !== 'healthy') {
        print_r($s);
        throw new Exception('error on test_updateNodeStatus');
    }
}

function tests() {
    test_Database_getUser();
    test_Database_insertUser();
    test_Database_updateUser();
    test_Database_getNode();
    test_Database_getNodes();
    test_Database_insertNode();
    test_Database_updateNode();
    test_Database_deleteNode();
    test_Database_getEdge();
    test_Database_getEdgeById();
    test_Database_getEdges();
    test_Database_insertEdge();
    test_Database_updateEdge();
    test_Database_deleteEdge();
    test_Database_getStatuses();
    test_Database_getNodeStatus();
    test_Database_updateNodeStatus();
}

tests();

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

echo "fim\n";

