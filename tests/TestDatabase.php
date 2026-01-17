<?php

declare(strict_types=1);

class TestDatabase extends TestAbstractTest
{
    private ?PDO $pdo;
    private ?LoggerInterface $logger;
    private ?Database $database;

    public function up(): void
    {
        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->logger = new Logger();
        $this->database = new Database($this->pdo, $this->logger);
    }

    public function down(): void
    {
        $this->pdo = null;
        $this->logger = null;
        $this->database = null;
    }

    public function testGetUser(): void
    {
        $user = $this->database->getUser('maria');
        if ($user !== null) {
            throw new Exception('should return null');
        }

        $user = $this->database->getUser('admin');
        if ($user['id'] !== 'admin' || $user['group'] !== 'admin') {
            print_r($user);
            throw new Exception('admin expected');
        }
    }

    public function testInsertUser(): void
    {
        $this->database->insertUser('maria', 'contributor');
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();

        if ($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
            throw new Exception('maria expected');
        }
        try {
            $this->database->insertUser('maria', 'contributor');
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error expected');
    }

    public function testUpdateUser(): void {
        $stmt = $this->pdo->prepare('insert into users (id, user_group) values (:id, :user_group)');
        $stmt->execute([':id' => 'maria', ':user_group' => 'contributor']);
        
        $this->database->updateUser('maria', 'admin');
        
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();
        
        if ($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
            throw new Exception('expected maria admin');
        }
        if ($this->database->updateUser('joao', 'contributor')) {
            throw new Exception('expected joao not found');
        }
    }

    public function testGetCategories(): void
    {
        $categories = $this->database->getCategories();
        $originalCount = count($categories);
        
        if ($originalCount === 0) {
            throw new Exception('should have categories');
        }

        $this->database->insertCategory('cat1', 'Category 1', 'box', 100, 50);
        $this->database->insertCategory('cat2', 'Category 2', 'box', 100, 50);

        $categories = $this->database->getCategories();
        if (count($categories) !== $originalCount + 2) {
            throw new Exception('should be two categories');
        }

        foreach ($categories as $key => $cat) {
            if ($cat['id'] !== 'cat1' && $cat['id'] !== 'cat2') {
                unset($categories[$key]);
            }
        }

        $categories = array_values($categories);


        if ($categories[0]['id'] !== 'cat1' || $categories[0]['name'] !== 'Category 1') {
            throw new Exception('error on category cat1');
        }

        if ($categories[1]['id'] !== 'cat2' || $categories[1]['name'] !== 'Category 2') {
            throw new Exception('error on category cat2');
        }
    }

    public function testGetTypes(): void
    {
        $types = $this->database->getTypes();
        $originalCount = count($types);
        
        if ($originalCount === 0) {
            throw new Exception('should have types');
        }

        $this->database->insertType('type1', 'Type 1');
        $this->database->insertType('type2', 'Type 2');

        $types = $this->database->getTypes();
        if (count($types) !== $originalCount + 2) {
            throw new Exception('should be two types');
        }

        foreach ($types as $key => $type) {
            if ($type['id'] !== 'type1' && $type['id'] !== 'type2') {
                unset($types[$key]);
            }
        }

        $types = array_values($types);

        if ($types[0]['id'] !== 'type1' || $types[0]['name'] !== 'Type 1') {
            throw new Exception('error on type type1');
        }

        if ($types[1]['id'] !== 'type2' || $types[1]['name'] !== 'Type 2') {
            throw new Exception('error on type type2');
        }
    }

    public function testGetNode(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([
            ':id' => 'node1',
            ':label' => 'Node 01',
            ':category' => 'business',
            ':type' => 'service',
            ':data' => json_encode(['running_on' => 'SRV01OP'])
        ]);

        $node = $this->database->getNode('node1');
        
        if ($node['id'] !== 'node1' || $node['label'] !== 'Node 01' || $node['category'] !== 'business' || $node['type'] !== 'service') {
            throw new Exception('error on getNode');
        }

        if ($node['data']['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if (!is_null($this->database->getNode('node2'))) {
            throw new Exception('null expected');
        }
    }

    public function testGetNodes(): void {
        $this->database->insertCategory('cat1', 'cat1', 'box', 100, 50);
        $this->database->insertCategory('cat2', 'cat2', 'box', 100, 50);
        $this->database->insertType('app', 'Application');
        $this->database->insertType('db', 'Database');
        
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');

        $stmt->execute([
            ':id' => 'node1',
            ':label' => 'Node 01',
            ':category' => 'cat1',
            ':type' => 'app',
            ':data' => json_encode(['running_on' => 'SRV01OP'])
        ]);
        sleep(1);
        $stmt->execute([
            ':id' => 'node2',
            ':label' => 'Node 02',
            ':category' => 'cat2',
            ':type' => 'db',
            ':data' => json_encode(['running_on' => 'SRV011P'])
        ]);

        $nodes = $this->database->getNodes();

        if (count($nodes) !== 2) {
            throw new Exception('error on testGetNodes');
        }

        if ($nodes[0]['id'] !== 'node1' || $nodes[0]['label'] !== 'Node 01' || $nodes[0]['category'] !== 'cat1' || $nodes[0]['type'] !== 'app') {
            throw new Exception('error on getNode');
        }

        if ($nodes[0]['data']['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]['id'] !== 'node2' || $nodes[1]['label'] !== 'Node 02' || $nodes[1]['category'] !== 'cat2' || $nodes[1]['type'] !== 'db') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]['data']['running_on'] !== 'SRV011P') {
            throw new Exception('error on getNode');
        }
    }

    public function testGetNodeParentOf(): void
    {
        $this->database->insertCategory('cat1', 'cat1', 'box', 100, 50);
        $this->database->insertCategory('cat2', 'cat2', 'box', 100, 50);
        $this->database->insertType('app', 'Application');
        $this->database->insertType('db', 'Database');
        
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');

        $this->database->insertNode('node1', 'Node 01', 'cat1', 'app', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'cat2', 'db', ['running_on' => 'SRV011P']);
        $this->database->insertNode('node3', 'Node 03', 'cat1', 'app', ['running_on' => 'SRV012P']);

        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        $this->database->insertEdge('edge2', 'node2', 'node3', 'label', ['b' => 'c']);

        $node = $this->database->getNodeParentOf('node2');

        if ($node['id'] !== 'node1' || $node['label'] !== 'Node 01' || $node['category'] !== 'cat1' || $node['type'] !== 'app') {
            throw new Exception('error on testGetNodeParentOf');
        }

        if ($node['data']['running_on'] !== 'SRV01OP') {
            throw new Exception('error on testGetNodeParentOf');
        }

        $node = $this->database->getNodeParentOf('node1');
        if ($node !== null) {
            throw new Exception('error on testGetNodeParentOf');
        }
    }

    public function testGetDependentNodesOf(): void {
        $this->database->insertCategory('cat1', 'cat1', 'box', 100, 50);
        $this->database->insertCategory('cat2', 'cat2', 'box', 100, 50);
        $this->database->insertType('app', 'Application');
        $this->database->insertType('db', 'Database');

        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');

        $this->database->insertNode('node1', 'Node 01', 'cat1', 'app', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'cat2', 'db', ['running_on' => 'SRV011P']);
        $this->database->insertNode('node3', 'Node 03', 'cat1', 'app', ['running_on' => 'SRV012P']);

        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        $this->database->insertEdge('edge2', 'node2', 'node3', 'label', ['b' => 'c']);

        $nodes = $this->database->getDependentNodesOf('node2');

        if (count($nodes) !== 1) {
            throw new Exception('error on testGetDependentNodesOf');
        }

        if ($nodes[0]['id'] !== 'node3' || $nodes[0]['label'] !== 'Node 03' || $nodes[0]['category'] !== 'cat1' || $nodes[0]['type'] !== 'app') {
            throw new Exception('error on getNode');
        }

        if ($nodes[0]['data']['running_on'] !== 'SRV012P') {
            throw new Exception('error on getNode');
        }
    }

    public function testInsertNode(): void {
        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $node = $this->database->getNode('node1');
        if ($node['id'] !== 'node1' || $node['label'] !== 'Node 01' || $node['category'] !== 'business' || $node['type'] !== 'service') {
            throw new Exception('error on testInsertNode');
        }
        if ($node['data']['running_on'] !== 'SRV01OP') {
            throw new Exception('error on testInsertNode');
        }
        try {
            $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testInsertNode');
    }

    public function testUpdateNode(): void {
        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $this->database->updateNode('node1', 'Novo Label', 'application', 'database', ['other' => 'diff']);
        $node = $this->database->getNode('node1');
        if ($node['id'] !== 'node1' || $node['label'] !== 'Novo Label' || $node['category'] !== 'application' || $node['type'] !== 'database') {
            throw new Exception('error on testUpdateNode');
        }
        if ($node['data']['other'] !== 'diff') {
            throw new Exception('error on testUpdateNode');
        }

        if ($this->database->updateNode('node2', 'Novo Label', 'application', 'database', ['other' => 'diff'])) {
            throw new Exception('error on testUpdateNode');
        }
    }

    public function testDeleteNode(): void {
        $node = $this->database->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on testDeleteNode');
        }
        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $node = $this->database->getNode('node1');
        if ($node['id'] !== 'node1' || $node['label'] !== 'Node 01' || $node['category'] !== 'business' || $node['type'] !== 'service') {
            throw new Exception('error on testDeleteNode');
        }

        // Test deleting the node
        if (!$this->database->deleteNode('node1')) {
            throw new Exception('error on testDeleteNode - delete should succeed');
        }

        // Verify node was deleted
        $node = $this->database->getNode('node1');
        if ($node !== null) {
            throw new Exception('error on testDeleteNode - node should be null after delete');
        }

        // Test deleting non-existent node
        if ($this->database->deleteNode('node2')) {
            throw new Exception('error on testDeleteNode - should return false for non-existent node');
        }
    }

    public function testGetEdge(): void {
        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge');
        }

        $this->database->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
        
        $this->database->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);
        
        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        
        $edge = $this->database->getEdge('node1', 'node2');
        
        if ($edge['id'] !== 'edge1' || $edge['source'] !== 'node1' || $edge['target'] !== 'node2') {
            throw new Exception('error on testGetEdge');
        }
        if ($edge['data']['a'] !== 'b') {
            throw new Exception('error on testGetEdge');
        }
    }

    public function testGetEdges(): void {
        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdges');
        }

        $edge = $this->database->getEdge('node2', 'node3');
        if ($edge !== null) {
            throw new Exception('error on testGetEdges');
        }

        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->database->insertNode('node3', 'Node 03', 'application', 'service', ['running_on' => 'SRV012P']);

        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        $this->database->insertEdge('edge2', 'node2', 'node3', 'label', ['b' => 'c']);

        $edges = $this->database->getEdges();
        if (count($edges) !== 2) {
            throw new Exception('error on testGetEdges');
        }

        if ($edges[0]['id'] !== 'edge1' || $edges[0]['source'] !== 'node1' || $edges[0]['target'] !== 'node2' || $edges[0]['data']['a'] !== 'b') {
            throw new Exception('error on testGetEdges');
        }

        if ($edges[1]['id'] !== 'edge2' || $edges[1]['source'] !== 'node2' || $edges[1]['target'] !== 'node3' || $edges[1]['data']['b'] !== 'c') {
            throw new Exception('error on testGetEdges');
        }
    }

    public function testInsertEdge(): void {
        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testInsertEdge');
        }

        $this->database->insertNode('node1', 'Node 01', 'application', 'service', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'business', 'database', ['running_on' => 'SRV011P']);

        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);

        $edge = $this->database->getEdge('node1', 'node2');

        if ($edge['id'] !== 'edge1' || $edge['source'] !== 'node1' || $edge['target'] !== 'node2') {
            throw new Exception('error on testInsertEdge');
        }

        if ($edge['data']['a'] !== 'b') {
            throw new Exception('error on testInsertEdge');
        }

        $this->database->insertEdge('edge2', 'node2', 'node1', 'label', ['a' => 'b']);
        $edge = $this->database->getEdge('node2', 'node1');

        if ($edge !== null) {
            throw new Exception('error on testInsertEdge');
        }

        try {
            $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        } catch (Exception $e) {
            return;
        }
        throw new Exception('error on testInsertEdge');
    }

    public function testUpdateEdge(): void {
        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testUpdateEdge');
        }

        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->database->insertNode('node3', 'Node 03', 'application', 'service', ['running_on' => 'SRV012P']);
        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);

        $this->database->updateEdge('edge1', 'node2', 'node3', 'label', ['x' => 'y']);

        $edge = $this->database->getEdge('node2', 'node3');

        if ($edge['id'] !== 'edge1' || $edge['source'] !== 'node2' || $edge['target'] !== 'node3') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($edge['data']['x'] !== 'y') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($this->database->updateEdge('edge3', 'node2', 'node3', 'label', ['x' => 'y'])) {
            throw new Exception('error on testUpdateEdge');
        }
    }

    public function testDeleteEdge(): void {
        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);
        $this->database->insertNode('node2', 'Node 02', 'application', 'database', ['running_on' => 'SRV011P']);
        $this->database->insertNode('node3', 'Node 03', 'application', 'service', ['running_on' => 'SRV012P']);
        $this->database->insertEdge('edge1', 'node1', 'node2', 'label', ['a' => 'b']);
        $this->database->insertEdge('edge2', 'node2', 'node3', 'label', ['b' => 'c']);

        if (count($this->database->getEdges()) !== 2) {
            throw new Exception('error on testDeleteEdge');
        }

        $this->database->deleteEdge('edge1');
        $this->database->deleteEdge('edge2');

        if (count($this->database->getEdges()) !== 0) {
            throw new Exception('error on testDeleteEdge');
        }

        if ($this->database->deleteEdge('edge6')) {
            throw new Exception('error on testDeleteEdge');
        }
    }

    public function testGetStatus(): void {
        $s = $this->database->getStatus();

        if (count($s) !== 0) {
            throw new Exception('error on testGetStatus');
        }

        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);

        $s = $this->database->getStatus();

        if (count($s) !== 1) {
            throw new Exception('error on testGetStatus');
        }

        if ($s[0][ModelStatus::STATUS_KEYNAME_NODE_ID] !== 'node1' || $s[0][ModelStatus::STATUS_KEYNAME_STATUS] !== null) {
            throw new Exception('error on testGetStatus');
        }
    }

    public function testGetNodeStatus(): void {
        $s = $this->database->getStatus();

        if (count($s) !== 0) {
            throw new Exception('error on testGetStatus');
        }

        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);

        $s = $this->database->getNodeStatus('node1');

        if ($s['id'] !== 'node1' || $s['status'] !== null) {
            throw new Exception('error on testGetStatus');
        }

        if (!is_null($this->database->getNodeStatus('node2'))) {
            throw new Exception('error on testGetStatus');
        }
    }

    public function testUpdateNodeStatus(): void {
        $s = $this->database->getStatus();

        if (count($s) !== 0) {
            throw new Exception('error on testUpdateNodeStatus');
        }

        $this->database->insertNode('node1', 'Node 01', 'business', 'service', ['running_on' => 'SRV01OP']);

        $this->database->updateNodeStatus('node1', 'healthy');

        $s = $this->database->getNodeStatus('node1');

        if ($s['id'] !== 'node1' || $s['status'] !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus');
        }

        try {
            $this->database->updateNodeStatus('node101', 'healthy');
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testUpdateNodeStatus');
    }

    public function testGetLogs(): void {
        $logs = $this->database->getLogs(2);
        if (count($logs) > 0) {
            throw new Exception('error on testGetLogs');
        }

        $this->database->insertLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
        sleep(1);
        $this->database->insertLog('node', 'node2', 'update', null, null, 'admin', '127.0.0.1');

        $logs = $this->database->getLogs(2);
        if (count($logs) !== 2) {
            throw new Exception('error on testGetLogs');
        }

        if ($logs[0]['entity_id'] !== 'node2') {
            throw new Exception('error on testGetLogs');
        }

        if ($logs[1]['entity_id'] !== 'node1') {
            throw new Exception('error on testGetLogs');
        }
    }

    public function testInsertAuditLog(): void {
        $this->database->insertLog('node', 'node1', 'update', null, null, 'admin', '127.0.0.1');
        $logs = $this->database->getLogs(2);
        if (count($logs) !== 1) {
            throw new Exception('error on testInsertAuditLog');
        }

        if ($logs[0]['entity_id'] !== 'node1') {
            throw new Exception('error on testInsertAuditLog');
        }
    }
}
