<?php

declare(strict_types=1);

require_once __DIR__ . '/graph.php';

ini_set('xdebug.mode', '1');
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

if (file_exists('database.log')) {
    @unlink('database.log');
}

if (file_exists('service.log')) {
    @unlink('service.log');
}

if (file_exists('controller.log')) {
    @unlink('controller.log');
}

function createConnection(): array
{
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');
    $graphDb = new GraphDatabase($pdo, $databaseLogger);
    return [$graphDb, $pdo];
}

function test_DatabaseException(): void {
    $sql = "SELECT 1";
    $params = ['id' => 1];
    $pdoe = new PDOException("pdo message", 1001, null);
    $dbe = new DatabaseException("database exception message", 0, $pdoe, $sql, $params);
    if ($dbe->getMessage() != 'database exception message') {
        throw new Exception('exception in test_DatabaseException');
    }

    if($dbe->getPrevious()->getMessage() != 'pdo message') {
        throw new Exception('exception in test_DatabaseException');
    }
}

function test_Database_getUser(): void {
    [$graphDb, $pdo] = createConnection();

    $user = $graphDb->getUser('maria');
    if ($user !== null) {
        throw new Exception('deveria retornar null');
    }

    $user = $graphDb->getUser('admin');
    if($user['id'] !== 'admin' || $user['user_group'] !== 'admin') {
        throw new Exception('admin expected');
    }
}

function test_Database_getUser_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE users');

    try {
        $graphDb->getUser('maria');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getUser_Exception');
}

function test_Database_insertUser(): void {
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertUser('maria', 'contributor');
    $user = $graphDb->getUser('maria');
    if($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
        throw new Exception('maria expected');
    }
}

function test_Database_insertUser_Exception(): void {
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE users');

    try {
        $graphDb->insertUser('maria', 'contributor');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_insertUser_Exception');
}

function test_Database_updateUser(): void
{
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertUser('maria', 'contributor');
    $graphDb->updateUser('maria', 'admin');
    $user = $graphDb->getUser('maria');
    if($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
        throw new Exception('expected maria admin');
    }
}

function test_Database_updateUser_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE users');

    try {
        $graphDb->updateUser('maria', 'contributor');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_updateUser_Exception');
}

function test_Database_getNode(): void {
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
        throw new Exception('error on getNode');
    }

    if ($node['data']['running_on'] != 'SRV01OP') {
        throw new Exception('error on getNode');
    }
}

function test_Database_getNode_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE nodes');

    try {
        $graphDb->getNode('node1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getNode_Exception');
}

function test_Database_getNodes(): void {
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);
    $nodes = $graphDb->getNodes();

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

function test_Database_getNodes_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE nodes');

    try {
        $graphDb->getNodes();
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getNodes_Exception');
}

function test_Database_insertNode(): void {
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
        throw new Exception('error on getNode');
    }

    if ($node['data']['running_on'] != 'SRV01OP') {
        throw new Exception('error on getNode');
    }
}

function test_Database_insertNode_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE nodes');

    try {
        $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_insertNode_Exception');
}

function test_Database_updateNode(): void {
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $graphDb->updateNode('node1', 'Novo Label', 'application', 'database', ['other' => 'diff']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Novo Label' || $node['category'] != 'application' || $node['type'] != 'database') {
        throw new Exception('error on test_updateNode');
    }

    if ($node['data']['other'] != 'diff') {
        throw new Exception('error on test_updateNode');
    }
}

function test_Database_updateNode_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE nodes');

    try {
        $graphDb->updateNode('node1', 'Novo Label', 'application', 'database', ['other' => 'diff']);
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_updateNode_Exception');
}

function test_Database_deleteNode(): void {
    [$graphDb, $pdo] = createConnection();
    $node = $graphDb->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_deleteNode');
    }
    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $node = $graphDb->getNode('node1');
    if($node['id'] != 'node1' || $node['label'] != 'Node 01' || $node['category'] != 'business' || $node['type'] != 'server') {
        throw new Exception('error on test_updateNode');
    }

    $graphDb->deleteNode('node1');
    $node = $graphDb->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_deleteNode');
    }
}

function test_Database_deleteNode_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE nodes');

    try {
        $graphDb->deleteNode('node1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_deleteNode_Exception');
}

function test_Database_getEdge(): void {
    [$graphDb, $pdo] = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_getEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
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

function test_Database_getEdge_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');

    try {
        $graphDb->getEdge('node1', 'node2');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getEdge_Exception');
}

function test_Database_getEdgeById(): void {
    [$graphDb, $pdo] = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_Database_getEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

    $edge = $graphDb->getEdgeById('edge1');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
        throw new Exception('error on test_Database_getEdge');
    }

    if ($edge['data']['a'] != 'b') {
        throw new Exception('error on test_Database_getEdge');
    }

    $edge = $graphDb->getEdgeById('edge2');
    if ($edge !== null) {
        throw new Exception('error on test_Database_getEdge');
    }
}

function test_Database_getEdgeById_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');
    try {
        $graphDb->getEdgeById('edge1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getEdgeById_Exception');
}

function test_Database_getEdges(): void {
    [$graphDb, $pdo] = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_getEdges');
    }

    $edge = $graphDb->getEdge('node2', 'node3');
    if ($edge !== null) {
        throw new Exception('error on test_getEdges');
    }

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);

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

function test_Database_getEdges_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');

    try {
        $graphDb->getEdges();
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getEdges_Exception');
}

function test_Database_insertEdge(): void {
    [$graphDb, $pdo] = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_Database_insertEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'application', 'application', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

    $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);

    $edge = $graphDb->getEdge('node1', 'node2');

    if($edge['id'] != 'edge1' || $edge['source'] != 'node1' || $edge['target'] != 'node2') {
        throw new Exception('error on test_Database_insertEdge');
    }

    if ($edge['data']['a'] != 'b') {
        throw new Exception('error on test_Database_insertEdge');
    }

    $graphDb->insertEdge('edge2', 'node2', 'node1', ['a' => 'b']);
    $edge = $graphDb->getEdge('node2', 'node1');

    if ($edge !== null) {
        throw new Exception('error on test_Database_insertEdge');
    }
}

function test_Database_insertEdge_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');

    try {
        $graphDb->insertEdge('edge1', 'node1', 'node2', ['a' => 'b']);
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_insertEdge_Exception');
}

function test_Database_updateEdge(): void {
    [$graphDb, $pdo] = createConnection();
    $edge = $graphDb->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_updateEdge');
    }

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);
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

function test_Database_updateEdge_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');

    try {
        $graphDb->updateEdge('edge1', 'node2', 'node3', ['x' => 'y']);
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_updateEdge_Exception');
}

function test_Database_deleteEdge(): void {
    [$graphDb, $pdo] = createConnection();

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);
    $graphDb->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
    $graphDb->insertNode('node3', 'Node 03', 'network', 'application', ['running_on' => 'SRV012P']);
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

function test_Database_deleteEdge_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE edges');

    try {
        $graphDb->deleteEdge('edge1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_deleteEdge_Exception');
}

function test_Database_getStatuses(): void {
    [$graphDb, $pdo] = createConnection();
    $s = $graphDb->getStatuses();

    if (count($s) != 0) {
        throw new Exception('error on test_getStatuses');
    }

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

    $s = $graphDb->getStatuses();

    if (count($s) != 1) {
        throw new Exception('error on test_getStatuses');
    }

    if ($s[0]['id'] != 'node1' || $s[0]['status'] !== null) {
        throw new Exception('error on test_getStatuses');
    }
}

function test_Database_getStatuses_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE status');

    try {
        $graphDb->getStatuses();
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getStatuses_Exception');
}

function test_Database_getNodeStatus(): void {
    [$graphDb, $pdo] = createConnection();
    $s = $graphDb->getStatuses();

    if (count($s) != 0) {
        throw new Exception('error on test_getStatuses');
    }

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

    $s = $graphDb->getNodeStatus('node1');

    if ($s['id'] != 'node1' || $s['status'] !== null) {
        throw new Exception('error on test_getStatuses');
    }
}

function test_Database_getNodeStatus_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE status');

    try {
        $graphDb->getNodeStatus('node1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getNodeStatus_Exception');
}

function test_Database_updateNodeStatus(): void {
    [$graphDb, $pdo] = createConnection();
    $s = $graphDb->getStatuses();

    if (count($s) != 0) {
        throw new Exception('error on test_updateNodeStatus');
    }

    $graphDb->insertNode('node1', 'Node 01', 'business', 'server', ['running_on' => 'SRV01OP']);

    $graphDb->updateNodeStatus('node1', 'healthy');

    $s = $graphDb->getNodeStatus('node1');

    if ($s['id'] != 'node1' || $s['status'] !== 'healthy') {
        throw new Exception('error on test_updateNodeStatus');
    }
}

function test_Database_updateNodeStatus_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE status');

    try {
        $graphDb->updateNodeStatus('node1', 'healthy');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_updateNodeStatus_Exception');
}

function test_Database_getLogs(): void
{
    [$graphDb, $pdo] = createConnection();
    $logs = $graphDb->getLogs(2);
    if(count($logs) > 0) {
        throw new Exception('problem on test_Database_getLogs');
    }

    $graphDb->insertAuditLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
    sleep(1);
    $graphDb->insertAuditLog('node', 'node2', 'update', null, null, 'admin', '127.0.0.1');

    $logs = $graphDb->getLogs(2);
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

function test_Database_getLogs_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE audit');

    try {
        $graphDb->getLogs(2);
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_getLogs_Exception');
}

function test_Database_insertAuditLog(): void
{
    [$graphDb, $pdo] = createConnection();
    $graphDb->insertAuditLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
    $logs = $graphDb->getLogs(2);
    if(count($logs) != 1) {
        throw new Exception('problem on test_Database_getLogs');
    }

    if ($logs[0]['entity_id'] != 'node1') {
        throw new Exception('problem on test_Database_getLogs');
    }
}

function test_Database_insertAuditLog_Exception(): void
{
    [$graphDb, $pdo] = createConnection();
    $pdo->exec('DROP TABLE audit');

    try {
        $graphDb->insertAuditLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
        return;
    } catch(DatabaseException $e) {
        return;
    }
    throw new Exception('other exception in test_Database_insertAuditLog_Exception');
}

function test_Database()
{
    test_DatabaseException();

    test_Database_getUser();
    test_Database_getUser_Exception();

    test_Database_insertUser();
    test_Database_insertUser_Exception();

    test_Database_updateUser();
    test_Database_updateUser_Exception();

    test_Database_getNode();
    test_Database_getNode_Exception();

    test_Database_getNodes();
    test_Database_getNodes_Exception();

    test_Database_insertNode();
    test_Database_insertNode_Exception();

    test_Database_updateNode();
    test_Database_updateNode_Exception();

    test_Database_deleteNode();
    test_Database_deleteNode_Exception();

    test_Database_getEdge();
    test_Database_getEdge_Exception();

    test_Database_getEdgeById();
    test_Database_getEdgeById_Exception();

    test_Database_getEdges();
    test_Database_getEdges_Exception();

    test_Database_insertEdge();
    test_Database_insertEdge_Exception();

    test_Database_updateEdge();
    test_Database_updateEdge_Exception();

    test_Database_deleteEdge();
    test_Database_deleteEdge_Exception();

    test_Database_getStatuses();
    test_Database_getStatuses_Exception();

    test_Database_getNodeStatus();
    test_Database_getNodeStatus_Exception();

    test_Database_updateNodeStatus();
    test_Database_updateNodeStatus_Exception();

    test_Database_getLogs();
    test_Database_getLogs_Exception();
    test_Database_insertAuditLog();
    test_Database_insertAuditLog_Exception();
}

#######################################################################################################
#######################################################################################################
#######################################################################################################
#######################################################################################################

function createService(): array
{
    GraphContext::update(new User('joao', new Group('consumer')), '127.0.0.1');
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');

    $graphDb = new GraphDatabase($pdo, $databaseLogger);

    $serviceLogger = new Logger('service.log');
    $graphService = new GraphService($graphDb, $serviceLogger);
    return [$graphService, $pdo];
}

function test_User()
{
    $user = new User('admin', new Group('admin'));
    $data = $user->toArray();
    if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
        throw new Exception('test_UserModel problem');
    }
}

function test_Group()
{
    $group = new Group('contributor');
    $data = $group->toArray();
    if($data['id'] != $group->getId()) {
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
    $edge1 = new Edge('node1', 'node2', ['weight' => '10']);

    $graph = new Graph([$node1, $node2], [$edge1]);

    if (count($graph->nodes) != 2) {
        throw new Exception('test_Graph problem - expected 2 nodes');
    }

    if (count($graph->edges) != 1) {
        throw new Exception('test_Graph problem - expected 1 edge');
    }

    $data = $graph->toArray();
    if (!isset($data['nodes']) || !isset($data['edges'])) {
        throw new Exception('test_Graph problem - missing keys in toArray');
    }

    if (count($data['nodes']) != 2 || count($data['edges']) != 1) {
        throw new Exception('test_Graph problem - toArray count mismatch');
    }
}

function test_Node()
{
    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);

    if ($node->getId() != 'node1' || $node->getLabel() != 'Node 01' || $node->getCategory() != 'business' || $node->getType() != 'server') {
        throw new Exception('test_Node problem - property mismatch');
    }

    $data = $node->getData();
    if ($data['key'] != 'value') {
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

    if ($status->getNodeId() != 'node1' || $status->getStatus() != 'healthy') {
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
        if ($s->getStatus() != $validStatus) {
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

function test_Edge()
{
    $edge = new Edge('node1', 'node2', ['weight' => '10']);

    if ($edge->getId() != 'node1-node2' || $edge->getSource() != 'node1' || $edge->getTarget() != 'node2') {
        throw new Exception('test_Edge problem - property mismatch');
    }

    $data = $edge->getData();
    if ($data['weight'] != '10') {
        throw new Exception('test_Edge problem - data mismatch');
    }

    $arr = $edge->toArray();
    if ($arr['source'] != 'node1' || $arr['target'] != 'node2') {
        throw new Exception('test_Edge problem - toArray mismatch');
    }

    if ($arr['data']['weight'] != '10') {
        throw new Exception('test_Edge problem - toArray data mismatch');
    }

    // Test with empty data
    $edge3 = new Edge('node5', 'node6');
    if (count($edge3->getData()) != 0) {
        throw new Exception('test_Edge problem - data should be empty array');
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

function test_Models()
{
    test_User();
    test_Group();
    test_Group_Exception();
    test_Graph();
    test_Node();
    test_NodeStatus();
    test_Edge();
    test_AuditLog();
}

function test_Service_getUser()
{
    [$service, $pdo] = createService();
    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }

    $user = $service->getUser('admin');
    
    if($user->getId() != 'admin' || $user->getGroup()->getId() != 'admin') {
        throw new Exception('error on test_Service_getUser');
    }
}

function test_Service_getUser_Exception(): void
{
    [$service, $pdo] = createService();
    $pdo->exec('DROP TABLE users');

    try {
        $service->getUser('node1');
    } catch(GraphServiceException $e) {
        return;
    }
    throw new Exception('error on test_Service_getUser_Exception');
}

function test_Service_insertUser()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $user = $service->getUser('maria');
    if ($user !== null) {
        throw new Exception('error on test_Service_getUser');
    }
    $service->insertUser(new User('maria', new Group('contributor')));
}

function test_Service_insertUser_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    
    $user = $service->getUser('maria');
    $pdo->exec('DROP TABLE users');

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
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $service->insertUser(new User('maria', new Group('contributor')));
    $service->updateUser(new User('maria', new Group('admin')));

    $user = $service->getUser('maria');
    if($user->getId() != 'maria' || $user->getGroup()->getId() != 'admin') {
        throw new Exception('error on test_Service_updateUser');
    }
}

function test_Service_updateUser_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $pdo->exec('DROP TABLE users');
    
    try {
        $service->updateUser(new User('maria', new Group('admin')));
    } catch(GraphServiceException $e) {
        return;
    }

    $user = $service->getUser('maria');
    if($user->id != 'maria' || $user->group->id != 'admin') {
        throw new Exception('error on test_Service_updateUser');
    }
}

function test_Service_getGraph()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge1 = new Edge('node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge1);

    $graph = $service->getGraph();

    if (count($graph->nodes) != 2) {
        throw new Exception('error on test_Service_getGraph - expected 2 nodes');
    }

    if (count($graph->edges) != 1) {
        throw new Exception('error on test_Service_getGraph - expected 1 edge');
    }
}

function test_Service_getGraph_Exception()
{
    [$service, $pdo] = createService();
    $pdo->exec('DROP TABLE edges');
    $pdo->exec('DROP TABLE nodes');
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    try {
        $service->getGraph();
    } catch(GraphServiceException $e) {
        return;
    }
    throw new Exception('error on test_Service_getGraph_Exception');
}

function test_Service_getNode()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = $service->getNode('node1');
    if ($node !== null) {
        throw new Exception('error on test_Service_getNode - should be null');
    }

    $newNode = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($newNode);

    $node = $service->getNode('node1');
    if ($node->getId() != 'node1' || $node->getLabel() != 'Node 01' || $node->getCategory() != 'business' || $node->getType() != 'server') {
        throw new Exception('error on test_Service_getNode');
    }

    $data = $node->getData();
    if ($data['key'] != 'value') {
        throw new Exception('error on test_Service_getNode - data mismatch');
    }
}

function test_Service_getNode_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE nodes');
    try {
        $service->getNode('node1');
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_getNode_Exception');
}

function test_Service_getNodes()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $nodes = $service->getNodes();
    if (count($nodes) != 0) {
        throw new Exception('error on test_Service_getNodes - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $nodes = $service->getNodes();
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

function test_Service_insertNode()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($node);

    $retrievedNode = $service->getNode('node1');
    if ($retrievedNode->getId() != 'node1' || $retrievedNode->getLabel() != 'Node 01') {
        throw new Exception('error on test_Service_insertNode');
    }

    // Test with contributor permission
    GraphContext::update(new User('maria', new Group('contributor')), '127.0.0.1');
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node2);

    $retrievedNode2 = $service->getNode('node2');
    if ($retrievedNode2->getId() != 'node2') {
        throw new Exception('error on test_Service_insertNode - contributor should be able to insert');
    }
}

function test_Service_insertNode_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $pdo->exec('DROP TABLE nodes');

    try {
        $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
        $service->insertNode($node);
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_insertNode_Exception');
}

function test_Service_updateNode()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);
    $service->insertNode($node);

    $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
    $service->updateNode($updatedNode);

    $retrievedNode = $service->getNode('node1');
    if ($retrievedNode->getLabel() != 'Updated Node' || $retrievedNode->getCategory() != 'application') {
        throw new Exception('error on test_Service_updateNode');
    }

    $data = $retrievedNode->getData();
    if ($data['key'] != 'newvalue') {
        throw new Exception('error on test_Service_updateNode - data not updated');
    }

    // try to update node not found
    $updatedNode = new Node('node5', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
    $exists = $service->updateNode($updatedNode);
    if(!is_null($exists)) {
        throw new Exception('error on test_Service_updateNode');
    }

}

function test_Service_updateNode_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE nodes');

    try {
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', ['key' => 'newvalue']);
        $service->updateNode($updatedNode);
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_updateNode_Exception');
}

function test_Service_deleteNode()
{
    [$service, $pdo] = createService();
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

    // try to delete node not found
    try {
        $service->deleteNode('node5');
    } catch(Exception $e) {
        throw $e;
    }
}

function test_Service_deleteNode_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE nodes');

    try {
        $service->deleteNode('node1');
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_deleteNode_Exception');
}

function test_Service_getEdge()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = $service->getEdge('node1', 'node2');
    if ($edge !== null) {
        throw new Exception('error on test_Service_getEdge - should be null');
    }

    $newEdge = new Edge('node1', 'node2', ['weight' => '10']);
    $service->insertEdge($newEdge);

    $edge = $service->getEdge('node1', 'node2');
    if ($edge->getId() != 'node1-node2' || $edge->getSource() != 'node1' || $edge->getTarget() != 'node2') {
        throw new Exception('error on test_Service_getEdge');
    }

    $data = $edge->getData();
    if ($data['weight'] != '10') {
        throw new Exception('error on test_Service_getEdge - data mismatch');
    }
}

function test_Service_getEdge_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE edges');

    try {
        $service->getEdge('node1', 'node2');
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_getEdge_Exception');
}

function test_Service_getEdges()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $edges = $service->getEdges();
    if (count($edges) != 0) {
        throw new Exception('error on test_Service_getEdges - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
    $service->insertNode($node1);
    $service->insertNode($node2);
    $service->insertNode($node3);

    $edge1 = new Edge('node1', 'node2', ['weight' => '10']);
    $edge2 = new Edge('node2', 'node3', ['weight' => '20']);
    $service->insertEdge($edge1);
    $service->insertEdge($edge2);

    $edges = $service->getEdges();
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

function test_Service_getEdges_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE edges');
    try{
        $service->getEdges();
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_getEdges_Exception');
}

function test_Service_insertEdge()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = new Edge('node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $retrievedEdge = $service->getEdge('node1', 'node2');
    if ($retrievedEdge->getId() != 'node1-node2' || $retrievedEdge->getSource() != 'node1' || $retrievedEdge->getTarget() != 'node2') {
        throw new Exception('error on test_Service_insertEdge');
    }
}

function test_Service_insertEdge_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE edges');

    try {
        $edge = new Edge('node1', 'node2', ['weight' => '10']);
        $service->insertEdge($edge);
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('error on test_Service_insertEdge_Exception');
}

function test_Service_updateEdge()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $node3 = new Node('node3', 'Node 03', 'network', 'server', ['key' => 'value3']);
    $service->insertNode($node1);
    $service->insertNode($node2);
    $service->insertNode($node3);

    $edge = new Edge('node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $updatedEdge = new Edge('node1', 'node2', ['weight' => '30']);
    $service->updateEdge($updatedEdge);

    // $retrievedEdge = $service->getEdge('node2', 'node3');
    // if ($retrievedEdge->source != 'node2' || $retrievedEdge->target != 'node3') {
    //     throw new Exception('error on test_Service_updateEdge');
    // }

    // if ($retrievedEdge->data['weight'] != '30') {
    //     throw new Exception('error on test_Service_updateEdge - data not updated');
    // }
}

function test_Service_updateEdge_Exception1()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    try {
        $updatedEdge = new Edge('node2', 'node3', ['weight' => '30']);
        $service->updateEdge($updatedEdge);
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_updateEdge_Exception');
}

function test_Service_updateEdge_Exception2()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $pdo->exec('DROP TABLE edges');

    try {
        $updatedEdge = new Edge('node2', 'node3', ['weight' => '30']);
        $service->updateEdge($updatedEdge);
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_updateEdge_Exception');
}

function test_Service_deleteEdge()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $edge = new Edge('node1', 'node2', ['weight' => '10']);
    $service->insertEdge($edge);

    $retrievedEdge = $service->getEdge('node1', 'node2');
    if ($retrievedEdge === null) {
        throw new Exception('error on test_Service_deleteEdge - edge not inserted');
    }

    $service->deleteEdge(new Edge('node1', 'node2'));

    $edges = $service->getEdges();
    
    if (count($edges) != 0) {
        throw new Exception('error on test_Service_deleteEdge - edge not deleted');
    }
}

function test_Service_deleteEdge_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE edges');

    try {
        $service->deleteEdge(new Edge('node1', 'node2'));
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_deleteEdge_Exception');
}

function test_Service_getStatuses()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $statuses = $service->getStatuses();
    if (count($statuses) != 0) {
        throw new Exception('error on test_Service_getStatuses - should be empty');
    }

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
    $service->insertNode($node1);
    $service->insertNode($node2);

    $service->updateNodeStatus(new NodeStatus('node1', 'healthy'));
    $service->updateNodeStatus(new NodeStatus('node2', 'unhealthy'));

    $statuses = $service->getStatuses();
    if (count($statuses) != 2) {
        throw new Exception('error on test_Service_getStatuses - expected 2 statuses');
    }
}

function test_Service_getStatuses_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE status');
    
    try {
        $service->getStatuses();
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_getStatuses_Exception');
}

function test_Service_getNodeStatus()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $service->insertNode($node1);

    $status = $service->getNodeStatus('node1');
    if ($status->getNodeId() != 'node1' || $status->getStatus() != 'unknown') {
        throw new Exception('error on test_Service_getNodeStatus - default should be unknown');
    }

    $service->updateNodeStatus(new NodeStatus('node1', 'healthy'));

    $status = $service->getNodeStatus('node1');
    if ($status->getNodeId() != 'node1' || $status->getStatus() != 'healthy') {
        throw new Exception('error on test_Service_getNodeStatus - status should be healthy');
    }
}

function test_Service_getNodeStatus_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE status');
    
    try {
        $service->getNodeStatus('node1');
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_getNodeStatus_Exception');
}

function test_Service_updateNodeStatus()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
    $service->insertNode($node1);

    $service->updateNodeStatus(new NodeStatus('node1', 'healthy'));

    $status = $service->getNodeStatus('node1');
    if ($status->getStatus() != 'healthy') {
        throw new Exception('error on test_Service_updateNodeStatus - status not set');
    }

    $service->updateNodeStatus(new NodeStatus('node1', 'maintenance'));

    $status = $service->getNodeStatus('node1');
    if ($status->getStatus() != 'maintenance') {
        throw new Exception('error on test_Service_updateNodeStatus - status not updated');
    }
}

function test_Service_updateNodeStatus_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    try {
        $service->updateNodeStatus(new NodeStatus('node1', 'maintenance'));
        return;
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_updateNodeStatus_Exception');
}

function test_Service_getLogs()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');

    $logs = $service->getLogs(10);
    if (count($logs) != 0) {
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

function test_Service_getLogs_Exception()
{
    [$service, $pdo] = createService();
    GraphContext::update(new User('admin', new Group('admin')), '127.0.0.1');
    $pdo->exec('DROP TABLE audit');

    try {
        $service->getLogs(10);
    } catch(GraphServiceException $e) {
        return;
    }

    throw new Exception('exception on test_Service_getLogs_Exception');
}

function test_Service()
{
    test_Service_getUser();
    test_Service_getUser_Exception();
    test_Service_insertUser();
    test_Service_insertUser_Exception();
    test_Service_updateUser();
    test_Service_updateUser_Exception();
    test_Service_getGraph();
    test_Service_getGraph_Exception();
    test_Service_getNode();
    test_Service_getNode_Exception();
    test_Service_getNodes();
    test_Service_insertNode();
    test_Service_insertNode_Exception();
    test_Service_updateNode();
    test_Service_updateNode_Exception();
    test_Service_deleteNode();
    test_Service_deleteNode_Exception();
    test_Service_getEdge();
    test_Service_getEdge_Exception();
    test_Service_getEdges();
    test_Service_getEdges_Exception();
    test_Service_insertEdge();
    test_Service_insertEdge_Exception();
    test_Service_updateEdge();
    test_Service_updateEdge_Exception1();
    test_Service_updateEdge_Exception2();
    test_Service_deleteEdge();
    test_Service_deleteEdge_Exception();
    test_Service_getStatuses();
    test_Service_getStatuses_Exception();
    test_Service_getNodeStatus();
    test_Service_getNodeStatus_Exception();
    test_Service_updateNodeStatus();
    test_Service_updateNodeStatus_Exception();
    test_Service_getLogs();
    test_Service_getLogs_Exception();
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

if (file_exists('controller.log')) {
    @unlink('controller.log');
}

echo "fim\n";
