<?php

declare(strict_types=1);

require_once __DIR__ . '/graph.php';

ini_set('xdebug.mode', '1');
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);


function getService(): GraphService
{
    GraphContext::update('joao', '127.0.0.1', 'consumer');
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');

    $graphDb = new GraphDatabase($pdo, $databaseLogger);

    $serviceLogger = new Logger('service.log');
    $graphService = new GraphService($graphDb, $serviceLogger);
    return $graphService;
}


function test_Service_getUser() {
    $service = getService();
    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }

    $user = $service->getUser('admin');
    
    if($user->id != 'admin' || $user->group->id != 'admin') {
        throw new Exception('error on test_Service_getUser');
    }
}

function test_Service_insertUser() {
    $service = getService();
    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }

    try {
        $service->insertUser(new User('maria', new Group('contributor')));
    } catch(GraphServiceException $e) {
        print($e);
    }
}

function test_Service_updateUser() {
}

function test_Service_getGraph() {
}

function test_Service_getNode() {
}

function test_Service_getNodes() {
}

function test_Service_insertNode() {
}

function test_Service_updateNode() {
}

function test_Service_deleteNode() {
}

function test_Service_getEdge() {
}

function test_Service_getEdges() {
}

function test_Service_insertEdge() {
}

function test_Service_updateEdge() {
}

function test_Service_deleteEdge() {
}

function test_Service_getStatuses() {
}

function test_Service_getNodeStatus() {
}

function test_Service_setNodeStatus() {
}

function test_Service_getLogs() {
}

function tests() {
    test_Service_getUser();
    test_Service_insertUser();
    test_Service_updateUser();
    test_Service_getGraph();
    test_Service_getNode();
    test_Service_getNodes();
    test_Service_insertNode();
    test_Service_updateNode();
    test_Service_deleteNode();
    test_Service_getEdge();
    test_Service_getEdges();
    test_Service_insertEdge();
    test_Service_updateEdge();
    test_Service_deleteEdge();
    test_Service_getStatuses();
    test_Service_getNodeStatus();
    test_Service_setNodeStatus();
    test_Service_getLogs();
}

tests();

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

echo "fim\n";

