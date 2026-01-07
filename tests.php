<?php

declare(strict_types=1);

use SebastianBergmann\RecursionContext\Context;

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

function test_Database()
{
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

#######################################################################################################
#######################################################################################################
#######################################################################################################
#######################################################################################################

function createService(): GraphService
{
    GraphContext::update(new User('joao', new Group('consumer')), '127.0.0.1');
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');

    $graphDb = new GraphDatabase($pdo, $databaseLogger);

    $serviceLogger = new Logger('service.log');
    $graphService = new GraphService($graphDb, $serviceLogger);
    return $graphService;
}

function test_User()
{
    $user = new User('admin', new Group('admin'));
    $data = $user->toArray();
    if($data['id'] != $user->id || $data['group']['id'] != 'admin') {
        throw new Exception('test_UserModel problem');
    }
}

function test_Group()
{
    $group = new Group('contributor');
    $data = $group->toArray();
    if($data['id'] != $group->id) {
        throw new Exception('test_Group problem');
    }
}

function test_Group_Exception()
{
    try {
        $group = new Group('xpto');
    } catch(InvalidArgumentException $e) {
        return;
    }
    throw new Exception('test_Group problem');
}

function test_Graph()
{
    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $edge1 = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);

    $graph = new Graph([$node1, $node2], [$edge1]);

    if (count($graph->nodes) != 2) {
        throw new Exception('test_Graph problem - expected 2 nodes');
    }

    if (count($graph->edges) != 1) {
        throw new Exception('test_Graph problem - expected 1 edge');
    }

    $data = $graph->toArray();
    if (!isset($data['nodes']) || !isset($data['edges']) || !isset($data['data']) || !isset($data['layout']) || !isset($data['styles'])) {
        throw new Exception('test_Graph problem - missing keys in toArray');
    }

    if (count($data['nodes']) != 2 || count($data['edges']) != 1) {
        throw new Exception('test_Graph problem - toArray count mismatch');
    }
}

function test_Node()
{
    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);

    if ($node->id != 'node1' || $node->label != 'Node 01' || $node->category != 'business' || $node->type != 'server') {
        throw new Exception('test_Node problem - property mismatch');
    }

    if ($node->data['key'] != 'value') {
        throw new Exception('test_Node problem - data mismatch');
    }

    $data = $node->toArray();
    if ($data['id'] != 'node1' || $data['label'] != 'Node 01' || $data['category'] != 'business' || $data['type'] != 'server') {
        throw new Exception('test_Node problem - toArray mismatch');
    }

    if ($data['data']['key'] != 'value') {
        throw new Exception('test_Node problem - toArray data mismatch');
    }

    // Test validation - invalid ID
    try {
        new Node('invalid@id', 'Label', 'business', 'server', []);
        throw new Exception('test_Node problem - should throw exception for invalid ID');
    } catch (InvalidArgumentException $e) {
        // Expected
    }

    // Test validation - label too long
    try {
        new Node('node2', str_repeat('a', 21), 'business', 'server', []);
        throw new Exception('test_Node problem - should throw exception for long label');
    } catch (InvalidArgumentException $e) {
        // Expected
    }

    // Test validation - invalid category
    try {
        new Node('node3', 'Label', 'invalid_category', 'server', []);
        throw new Exception('test_Node problem - should throw exception for invalid category');
    } catch (InvalidArgumentException $e) {
        // Expected
    }

    // Test validation - invalid type
    try {
        new Node('node4', 'Label', 'business', 'invalid_type', []);
        throw new Exception('test_Node problem - should throw exception for invalid type');
    } catch (InvalidArgumentException $e) {
        // Expected
    }
}

function test_NodeStatus()
{
    $status = new NodeStatus('node1', 'healthy');

    if ($status->nodeId != 'node1' || $status->status != 'healthy') {
        throw new Exception('test_NodeStatus problem - property mismatch');
    }

    $data = $status->toArray();
    if ($data['node_id'] != 'node1' || $data['status'] != 'healthy') {
        throw new Exception('test_NodeStatus problem - toArray mismatch');
    }

    // Test all valid statuses
    $validStatuses = ['unknown', 'healthy', 'unhealthy', 'maintenance'];
    foreach ($validStatuses as $validStatus) {
        $s = new NodeStatus('node2', $validStatus);
        if ($s->status != $validStatus) {
            throw new Exception('test_NodeStatus problem - valid status not accepted: ' . $validStatus);
        }
    }

    // Test validation - invalid status
    try {
        new NodeStatus('node3', 'invalid_status');
        throw new Exception('test_NodeStatus problem - should throw exception for invalid status');
    } catch (InvalidArgumentException $e) {
        // Expected
    }
}

function test_NodeStatuses()
{
    $nodeStatuses = new NodeStatuses();

    if (count($nodeStatuses->statuses) != 0) {
        throw new Exception('test_NodeStatuses problem - should be empty initially');
    }

    $status1 = new NodeStatus('node1', 'healthy');
    $status2 = new NodeStatus('node2', 'unhealthy');
    $status3 = new NodeStatus('node3', 'maintenance');

    $nodeStatuses->addStatus($status1);
    $nodeStatuses->addStatus($status2);
    $nodeStatuses->addStatus($status3);

    if (count($nodeStatuses->statuses) != 3) {
        throw new Exception('test_NodeStatuses problem - expected 3 statuses');
    }

    if ($nodeStatuses->statuses[0]->nodeId != 'node1' || $nodeStatuses->statuses[0]->status != 'healthy') {
        throw new Exception('test_NodeStatuses problem - first status mismatch');
    }

    if ($nodeStatuses->statuses[1]->nodeId != 'node2' || $nodeStatuses->statuses[1]->status != 'unhealthy') {
        throw new Exception('test_NodeStatuses problem - second status mismatch');
    }

    if ($nodeStatuses->statuses[2]->nodeId != 'node3' || $nodeStatuses->statuses[2]->status != 'maintenance') {
        throw new Exception('test_NodeStatuses problem - third status mismatch');
    }
}

function test_Nodes()
{
    $nodes = new Nodes();

    if (count($nodes->nodes) != 0) {
        throw new Exception('test_Nodes problem - should be empty initially');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);

    $nodes->addNode($node1);
    $nodes->addNode($node2);
    $nodes->addNode($node3);

    if (count($nodes->nodes) != 3) {
        throw new Exception('test_Nodes problem - expected 3 nodes');
    }

    if ($nodes->nodes[0]->id != 'node1' || $nodes->nodes[0]->label != 'Node 01') {
        throw new Exception('test_Nodes problem - first node mismatch');
    }

    if ($nodes->nodes[1]->id != 'node2' || $nodes->nodes[1]->label != 'Node 02') {
        throw new Exception('test_Nodes problem - second node mismatch');
    }

    if ($nodes->nodes[2]->id != 'node3' || $nodes->nodes[2]->label != 'Node 03') {
        throw new Exception('test_Nodes problem - third node mismatch');
    }
}

function test_Edge()
{
    $edge = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);

    if ($edge->id != 'edge1' || $edge->source != 'node1' || $edge->target != 'node2') {
        throw new Exception('test_Edge problem - property mismatch');
    }

    if ($edge->data['weight'] != '10') {
        throw new Exception('test_Edge problem - data mismatch');
    }

    $data = $edge->toArray();
    if ($data['id'] != 'edge1' || $data['source'] != 'node1' || $data['target'] != 'node2') {
        throw new Exception('test_Edge problem - toArray mismatch');
    }

    if ($data['data']['weight'] != '10') {
        throw new Exception('test_Edge problem - toArray data mismatch');
    }

    // Test with null id
    $edge2 = new Edge(null, 'node3', 'node4', []);
    if ($edge2->id !== null) {
        throw new Exception('test_Edge problem - id should be null');
    }

    if ($edge2->source != 'node3' || $edge2->target != 'node4') {
        throw new Exception('test_Edge problem - source/target mismatch with null id');
    }

    // Test with empty data
    $edge3 = new Edge('edge3', 'node5', 'node6');
    if (count($edge3->data) != 0) {
        throw new Exception('test_Edge problem - data should be empty array');
    }
}

function test_Edges()
{
    $edges = new Edges();

    if (count($edges->edges) != 0) {
        throw new Exception('test_Edges problem - should be empty initially');
    }

    $edge1 = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $edge2 = new Edge('edge2', 'node2', 'node3', ['weight' => '20']);
    $edge3 = new Edge('edge3', 'node3', 'node4', ['weight' => '30']);

    $edges->addEdge($edge1);
    $edges->addEdge($edge2);
    $edges->addEdge($edge3);

    if (count($edges->edges) != 3) {
        throw new Exception('test_Edges problem - expected 3 edges');
    }

    if ($edges->edges[0]->id != 'edge1' || $edges->edges[0]->source != 'node1' || $edges->edges[0]->target != 'node2') {
        throw new Exception('test_Edges problem - first edge mismatch');
    }

    if ($edges->edges[1]->id != 'edge2' || $edges->edges[1]->source != 'node2' || $edges->edges[1]->target != 'node3') {
        throw new Exception('test_Edges problem - second edge mismatch');
    }

    if ($edges->edges[2]->id != 'edge3' || $edges->edges[2]->source != 'node3' || $edges->edges[2]->target != 'node4') {
        throw new Exception('test_Edges problem - third edge mismatch');
    }
}

function test_AuditLog()
{
    $oldData = ['id' => 'node1', 'label' => 'Old Label'];
    $newData = ['id' => 'node1', 'label' => 'New Label'];

    $log = new AuditLog('node', 'node1', 'update', $oldData, $newData);

    if ($log->entityType != 'node' || $log->entityId != 'node1' || $log->action != 'update') {
        throw new Exception('test_AuditLog problem - property mismatch');
    }

    if ($log->oldData['label'] != 'Old Label') {
        throw new Exception('test_AuditLog problem - oldData mismatch');
    }

    if ($log->newData['label'] != 'New Label') {
        throw new Exception('test_AuditLog problem - newData mismatch');
    }

    // Test with null data
    $log2 = new AuditLog('node', 'node2', 'insert', null, ['id' => 'node2']);
    if ($log2->oldData !== null) {
        throw new Exception('test_AuditLog problem - oldData should be null');
    }

    if ($log2->newData['id'] != 'node2') {
        throw new Exception('test_AuditLog problem - newData mismatch for insert');
    }

    // Test delete action with null newData
    $log3 = new AuditLog('edge', 'edge1', 'delete', ['id' => 'edge1'], null);
    if ($log3->newData !== null) {
        throw new Exception('test_AuditLog problem - newData should be null for delete');
    }

    if ($log3->oldData['id'] != 'edge1') {
        throw new Exception('test_AuditLog problem - oldData mismatch for delete');
    }
}

function test_AuditLogs()
{
    $auditLogs = new AuditLogs();

    if (count($auditLogs->logs) != 0) {
        throw new Exception('test_AuditLogs problem - should be empty initially');
    }

    $log1 = new AuditLog('node', 'node1', 'insert', null, ['id' => 'node1', 'label' => 'Node 01']);
    $log2 = new AuditLog('node', 'node1', 'update', ['id' => 'node1', 'label' => 'Node 01'], ['id' => 'node1', 'label' => 'Updated Node']);
    $log3 = new AuditLog('node', 'node1', 'delete', ['id' => 'node1', 'label' => 'Updated Node'], null);

    $auditLogs->addLog($log1);
    $auditLogs->addLog($log2);
    $auditLogs->addLog($log3);

    if (count($auditLogs->logs) != 3) {
        throw new Exception('test_AuditLogs problem - expected 3 logs');
    }

    if ($auditLogs->logs[0]->action != 'insert' || $auditLogs->logs[0]->entityType != 'node') {
        throw new Exception('test_AuditLogs problem - first log mismatch');
    }

    if ($auditLogs->logs[1]->action != 'update' || $auditLogs->logs[1]->entityId != 'node1') {
        throw new Exception('test_AuditLogs problem - second log mismatch');
    }

    if ($auditLogs->logs[2]->action != 'delete' || $auditLogs->logs[2]->newData !== null) {
        throw new Exception('test_AuditLogs problem - third log mismatch');
    }
}

function test_Models()
{
    test_User();
    test_Group();
    test_Group_Exception();
    test_Graph();
    test_Node();
    test_NodeStatus();
    test_NodeStatuses();
    test_Nodes();
    test_Edge();
    test_Edges();
    test_AuditLog();
    test_AuditLogs();
}

function test_Service_getUser()
{
    $service = createService();
    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }

    $user = $service->getUser('admin');
    
    if($user->id != 'admin' || $user->group->id != 'admin') {
        throw new Exception('error on test_Service_getUser');
    }
}

function test_Service_insertUser()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }
    $service->insertUser(new User('maria', new Group('contributor')));
}

function test_Service_insertUserException()
{
    $service = createService();
    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }

    try {
        $service->insertUser(new User('maria', new Group('contributor')));
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_insertUserException');
}

function test_Service_updateUser()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $service->insertUser(new User('maria', new Group('contributor')));
    $service->updateUser(new User('maria', new Group('admin')));

    $user = $service->getUser('maria');
    if($user->id != 'maria' || $user->group->id != 'admin') {
        throw new Exception('error on test_Service_updateUser');
    }
}

function test_Service_getGraph()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge1 = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge1);

    $graph = $service->getGraph();

    if (count($graph->nodes) != 2) {
        throw new Exception('error on test_Service_getGraph - expected 2 nodes');
    }

    if (count($graph->edges) != 1) {
        throw new Exception('error on test_Service_getGraph - expected 1 edge');
    }
}

function test_Service_getNode()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = $service->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_Service_getNode - should be null');
    }

    $newNode = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($newNode);

    $node = $service->getNode('node1');
    if ($node->id != 'node1' || $node->label != 'Node 01' || $node->category != 'business' || $node->type != 'server') {
        throw new Exception('error on test_Service_getNode');
    }

    if ($node->data['key'] != 'value') {
        throw new Exception('error on test_Service_getNode - data mismatch');
    }
}

function test_Service_getNodes()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $nodes = $service->getNodes();
    if (count($nodes->nodes) != 0) {
        throw new Exception('error on test_Service_getNodes - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $nodes = $service->getNodes();
    if (count($nodes->nodes) != 2) {
        throw new Exception('error on test_Service_getNodes - expected 2 nodes');
    }

    if ($nodes->nodes[0]->id != 'node1' || $nodes->nodes[0]->label != 'Node 01') {
        throw new Exception('error on test_Service_getNodes - first node mismatch');
    }

    if ($nodes->nodes[1]->id != 'node2' || $nodes->nodes[1]->label != 'Node 02') {
        throw new Exception('error on test_Service_getNodes - second node mismatch');
    }
}

function test_Service_insertNode()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($node);

    $retrievedNode = $service->getNode('node1');
    if ($retrievedNode->id != 'node1' || $retrievedNode->label != 'Node 01') {
        throw new Exception('error on test_Service_insertNode');
    }

    // Test with contributor permission
    GraphContext::update(new User('maria', new Group('contributor')), '127.0.0.1');
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node2);

    $retrievedNode2 = $service->getNode('node2');
    if ($retrievedNode2->id != 'node2') {
        throw new Exception('error on test_Service_insertNode - contributor should be able to insert');
    }
}

function test_Service_updateNode()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($node);

    $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
    $service->updateNode($updatedNode);

    $retrievedNode = $service->getNode('node1');
    if ($retrievedNode->label != 'Updated Node' || $retrievedNode->category != 'application') {
        throw new Exception('error on test_Service_updateNode');
    }

    if ($retrievedNode->data['key'] != 'newvalue') {
        throw new Exception('error on test_Service_updateNode - data not updated');
    }
}

function test_Service_deleteNode()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($node);

    $retrievedNode = $service->getNode('node1');
    if ($retrievedNode === null) {
        throw new Exception('error on test_Service_deleteNode - node not inserted');
    }

    $service->deleteNode('node1');

    $deletedNode = $service->getNode('node1');
    if ($deletedNode !== null) {
        throw new Exception('error on test_Service_deleteNode - node not deleted');
    }
}

function test_Service_getEdge()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = $service->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_Service_getEdge - should be null');
    }

    $newEdge = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $service->insertEdge($newEdge);

    $edge = $service->getEdge('node1', 'node2');
    if ($edge->id != 'edge1' || $edge->source != 'node1' || $edge->target != 'node2') {
        throw new Exception('error on test_Service_getEdge');
    }

    if ($edge->data['weight'] != '10') {
        throw new Exception('error on test_Service_getEdge - data mismatch');
    }
}

function test_Service_getEdges()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $edges = $service->getEdges();
    if (count($edges->edges) != 0) {
        throw new Exception('error on test_Service_getEdges - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
    $service->insertNode($node1);
    $service->insertNode($node2);
    $service->insertNode($node3);

    $edge1 = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $edge2 = new Edge('edge2', 'node2', 'node3', ['weight' => '20']);
    $service->insertEdge($edge1);
    $service->insertEdge($edge2);

    $edges = $service->getEdges();
    if (count($edges->edges) != 2) {
        throw new Exception('error on test_Service_getEdges - expected 2 edges');
    }

    if ($edges->edges[0]->id != 'edge1' || $edges->edges[0]->source != 'node1') {
        throw new Exception('error on test_Service_getEdges - first edge mismatch');
    }

    if ($edges->edges[1]->id != 'edge2' || $edges->edges[1]->source != 'node2') {
        throw new Exception('error on test_Service_getEdges - second edge mismatch');
    }
}

function test_Service_insertEdge()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $retrievedEdge = $service->getEdge('node1', 'node2');
    if ($retrievedEdge->id != 'edge1' || $retrievedEdge->source != 'node1' || $retrievedEdge->target != 'node2') {
        throw new Exception('error on test_Service_insertEdge');
    }
}

function test_Service_updateEdge()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
    $service->insertNode($node1);
    $service->insertNode($node2);
    $service->insertNode($node3);

    $edge = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $updatedEdge = new Edge('edge1', 'node2', 'node3', ['weight' => '30']);
    $service->updateEdge($updatedEdge);

    $retrievedEdge = $service->getEdge('node2', 'node3');
    if ($retrievedEdge->source != 'node2' || $retrievedEdge->target != 'node3') {
        throw new Exception('error on test_Service_updateEdge');
    }

    if ($retrievedEdge->data['weight'] != '30') {
        throw new Exception('error on test_Service_updateEdge - data not updated');
    }
}

function test_Service_deleteEdge()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = new Edge('edge1', 'node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $retrievedEdge = $service->getEdge('node1', 'node2');
    if ($retrievedEdge === null) {
        throw new Exception('error on test_Service_deleteEdge - edge not inserted');
    }

    $service->deleteEdge('edge1');

    $edges = $service->getEdges();
    if (count($edges->edges) != 0) {
        throw new Exception('error on test_Service_deleteEdge - edge not deleted');
    }
}

function test_Service_getStatuses()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $statuses = $service->getStatuses();
    if (count($statuses->statuses) != 0) {
        throw new Exception('error on test_Service_getStatuses - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $service->setNodeStatus(new NodeStatus('node1', 'healthy'));
    $service->setNodeStatus(new NodeStatus('node2', 'unhealthy'));

    $statuses = $service->getStatuses();
    if (count($statuses->statuses) != 2) {
        throw new Exception('error on test_Service_getStatuses - expected 2 statuses');
    }
}

function test_Service_getNodeStatus()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $service->insertNode($node1);

    $status = $service->getNodeStatus('node1');
    if ($status->nodeId != 'node1' || $status->status != 'unknown') {
        throw new Exception('error on test_Service_getNodeStatus - default should be unknown');
    }

    $service->setNodeStatus(new NodeStatus('node1', 'healthy'));

    $status = $service->getNodeStatus('node1');
    if ($status->nodeId != 'node1' || $status->status != 'healthy') {
        throw new Exception('error on test_Service_getNodeStatus - status should be healthy');
    }
}

function test_Service_setNodeStatus()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $service->insertNode($node1);

    $service->setNodeStatus(new NodeStatus('node1', 'healthy'));

    $status = $service->getNodeStatus('node1');
    if ($status->status != 'healthy') {
        throw new Exception('error on test_Service_setNodeStatus - status not set');
    }

    $service->setNodeStatus(new NodeStatus('node1', 'maintenance'));

    $status = $service->getNodeStatus('node1');
    if ($status->status != 'maintenance') {
        throw new Exception('error on test_Service_setNodeStatus - status not updated');
    }
}

function test_Service_getLogs()
{
    $service = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $logs = $service->getLogs(10);
    if (count($logs->logs) != 0) {
        throw new Exception('error on test_Service_getLogs - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $service->insertNode($node1);
    sleep(1);

    $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
    $service->updateNode($updatedNode);
    sleep(1);

    $service->deleteNode('node1');
    sleep(1);

    $logs = $service->getLogs(10);
    if (count($logs->logs) != 3) {
        throw new Exception('error on test_Service_getLogs - expected 3 log entries (insert, update, delete)');
    }

    if ($logs->logs[0]->action != 'delete' || $logs->logs[0]->entityType != 'node') {
        throw new Exception('error on test_Service_getLogs - first log should be delete');
    }

    if ($logs->logs[1]->action != 'update' || $logs->logs[1]->entityType != 'node') {
        throw new Exception('error on test_Service_getLogs - second log should be update');
    }

    if ($logs->logs[2]->action != 'insert' || $logs->logs[2]->entityType != 'node') {
        throw new Exception('error on test_Service_getLogs - third log should be insert');
    }
}

function test_Service()
{
    test_Service_getUser();
    test_Service_insertUser();
    test_Service_insertUserException();
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


test_Database();
test_Models();
test_Service();

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

if (file_exists('database.log')) {
    @unlink('database.log');
}

if (file_exists('service.log')) {
    @unlink('service.log');
}

echo "fim\n";
