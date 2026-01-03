<?php

require_once dirname(__DIR__) . '/Supergraph.php';

GraphContext::$user = new User('admin', '127.0.0.1', new Group('admin'));

$pdo = GraphDatabase::createConnection('sqlite::memory:');
$graphDb = new GraphDatabase($pdo);
$graphService = new GraphService($graphDb);
$graphController = new GraphController($graphService);

$insertNodeReq = new Request();
$insertNodeReq->data = ['id' => 'node1', 'label' => 'node1', 'category' => 'business', 'type' => 'server', 'data' => ['info' => 'first node']];
$resp = $graphController->insertNode($insertNodeReq);
print_r($resp);

