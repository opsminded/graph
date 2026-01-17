<?php

declare(strict_types=1);

class TestHTTPRequestException extends TestAbstractTest
{
    public function testHTTPRequestException(): void
    {
        $req = new HTTPRequestException('message', ['key' => 'val'], ['id' => ''], '/get');
        if($req->getData() != ['key' => 'val']) {
            throw new Exception('problem on TestHTTPRequestException');
        }

        if($req->getParams() != ['id' => '']) {
            throw new Exception('problem on TestHTTPRequestException');
        }

        if($req->getPath() != '/get') {
            throw new Exception('problem on TestHTTPRequestException');
        }
    }
}
#####################################

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

#####################################

class TestHTTPRequestRouter extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?HTTPControllerInterface $controller;

    private ?HelperImages $imagesHelper;
    private ?HelperCytoscape $cytoscapeHelper;

    private ?HTTPRequestRouter $router;

    public function up(): void
    {
        include __DIR__ . "/www/images/compiled_images.php";

        $_GET = [];
        $_SERVER = [];

        $this->pdo = Database::createConnection('sqlite::memory:');

        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->database = new Database($this->pdo, $this->databaseLogger);

        $this->imagesHelper = new HelperImages($images);
        $this->cytoscapeHelper = new HelperCytoscape($this->database, $this->imagesHelper, 'http://localhost/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new HTTPController($this->service, $this->cytoscapeHelper, $this->controllerLogger);
        $this->router = new HTTPRequestRouter($this->controller);
    }

    public function down(): void
    {
        $this->router = null;
        $this->controller = null;
        $this->cytoscapeHelper = null;
        $this->imagesHelper = null;
        $this->service = null;
        $this->database = null;

        $this->databaseLogger = null;
        $this->serviceLogger = null;
        $this->controllerLogger = null;

        $this->pdo = null;

        $_GET = [];
        $_SERVER = [];
    }

    public function testHTTPRequestRouter(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        $req = new HTTPRequest();
        $req->data[ModelUser::USER_KEYNAME_ID] = 'joao';
        $req->data[ModelUser::USER_KEYNAME_GROUP] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 201 || $resp->message !== 'user created' || $resp->data['id'] !== 'joao' || $resp->data['group']['id'] !== 'contributor') {
            print_r($resp);
            throw new Exception('error on testHTTPRequestRouter');
        }
    }

    public function testHTTPRequestRouterException(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updataUser';
        $req = new HTTPRequest();
        $req->data[ModelUser::USER_KEYNAME_ID] = 'joao';
        $req->data[ModelUser::USER_KEYNAME_GROUP] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 500 || $resp->message !== 'method not found in list') {
            throw new Exception('error on testHTTPRequestRouterException');
        }
    }
}
#####################################

class TestHTTPInternalServerErrorResponse extends TestAbstractTest
{
    public function testHTTPInternalServerErrorResponse(): void
    {
        $resp = new HTTPInternalServerErrorResponse('database error', ['key' => 'val']);
        if($resp->code != 500) {
            throw new Exception('problem on code TestHTTPInternalServerErrorResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status TestHTTPInternalServerErrorResponse');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on TestHTTPInternalServerErrorResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestHTTPInternalServerErrorResponse');
        }
    }
}
#####################################

class TestHTTPNotFoundResponse extends TestAbstractTest
{
    public function testHTTPNotFoundResponse(): void
    {
        $resp = new HTTPNotFoundResponse('node not found', ['key' => 'val']);
        if($resp->code != 404) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node not found") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}
#####################################

class TestHTTPForbiddenResponse extends TestAbstractTest
{
    public function testHTTPForbiddenResponse(): void
    {
        $resp = new HTTPForbiddenResponse('node not created', ['key' => 'val']);
        if($resp->code != 403) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node not created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}
#####################################

final class TestHTTPOKResponse extends TestAbstractTest
{
    public function testHTTPOKResponse(): void
    {
        $resp = new HTTPOKResponse('node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}
#####################################

class TestModelGraph extends TestAbstractTest
{
    public function testGraphConstructor(): void
    {
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        
        $edge1 = new ModelEdge('node1', 'node2', 'lbl node1', ['weight' => '10']);

        $graph = new ModelGraph([$node1, $node2], [$edge1]);
        
        if (count($graph->getNodes()) != 2) {
            throw new Exception('TODO message. Node quantity');
        }

        if (count($graph->getEdges()) != 1) {
            throw new Exception('TODO message. Edge quantity');
        }

        $data = $graph->toArray();
        if (!isset($data['nodes']) || !isset($data['edges'])) {
            throw new Exception('TODO message');
        }

        if (count($data['nodes']) != 2 || count($data['edges']) != 1) {
            throw new Exception('TODO message');
        }
    }
}

#####################################

class TestModelUser extends TestAbstractTest
{
    public function testUserConstructor(): void
    {
        $user = new ModelUser('admin', new ModelGroup('admin'));
        $data = $user->toArray();
        if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
            throw new Exception('test_UserModel problem');
        }
    }
}

#####################################

class TestHelperContext extends TestAbstractTest
{
    public function testGraphContextUpdate(): void
    {
        HelperContext::update('maria', 'admin', '192.168.0.1');
        if (HelperContext::getUser() != 'maria') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperContext::getGroup() != 'admin') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperContext::getClientIP() != '192.168.0.1') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }
    }
}

#####################################

class TestModelEdge extends TestAbstractTest
{
    public function testEdgeConstruct(): void
    {
        $edge = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);

        if ($edge->getId() != 'node1-node2' || $edge->getSource() != 'node1' || $edge->getTarget() != 'node2') {
            throw new Exception('testEdgeConstruct');
        }

        $data = $edge->getData();
        if ($data['weight'] != '10') {
            throw new Exception('testEdgeConstruct');
        }

        $arr = $edge->toArray();
        if ($arr['source'] != 'node1' || $arr['target'] != 'node2') {
            throw new Exception('testEdgeConstruct');
        }

        if ($arr['data']['weight'] != '10') {
            throw new Exception('testEdgeConstruct');
        }

        // Test with empty data
        $edge3 = new ModelEdge('node5', 'node6', 'label');
        if (count($edge3->getData()) != 0) {
            throw new Exception('testEdgeConstruct');
        }
    }
}

#####################################

class TestHTTPResponse extends TestAbstractTest
{
    public function testHTTPResponse(): void
    {
        $resp = new HTTPResponse(200, 'success', 'node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testHTTPResponse');
        }

        ob_start();
        $resp->send();
        $content = ob_get_clean();

        $data = json_decode($content, true);

        if ($data['code'] != 200) {
            throw new Exception('problem on testHTTPResponse');
        }
    }
}
#####################################

class TestHTTPCreatedResponse extends TestAbstractTest
{
    public function testHTTPCreatedResponse(): void
    {
        $resp = new HTTPCreatedResponse('node created', ['key' => 'val']);
        if($resp->code != 201) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}
#####################################

class TestHelperLogger extends TestAbstractTest
{
    public function testLoggerConstructor(): void
    {
        
    }
}
#####################################

final class TestHTTPController extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?HTTPControllerInterface $controller;

    private ?HelperImages $imagesHelper;
    private ?HelperCytoscape $cytoscapeHelper;
    

    public function up(): void
    {
        include __DIR__ . "/www/images/compiled_images.php";

        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->imagesHelper = new HelperImages($images);
        
        $this->database = new Database($this->pdo, $this->databaseLogger);

        $this->cytoscapeHelper = new HelperCytoscape($this->database, $this->imagesHelper, 'http://example.com/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new HTTPController($this->service, $this->cytoscapeHelper, $this->controllerLogger);
    }

    public function down(): void
    {
        $this->controller = null;
        $this->cytoscapeHelper = null;
        $this->imagesHelper = null;
        $this->service = null;
        $this->database = null;

        $this->databaseLogger = null;
        $this->serviceLogger = null;
        $this->controllerLogger = null;

        $this->pdo = null;

        $_GET = [];
        $_SERVER = [];
    }

    public function testGetUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getUser';
        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getUser\'') {
            print_r($resp);
            throw new Exception('error on testGetUser 1');
        }
        
        $_GET['id'] = 'maria';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getUser';

        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->message !== 'user not found') {
            throw new Exception('error on testGetUser 2');
        }

        $this->database->insertUser('maria', 'contributor');

        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'user found' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testGetUser 3');
        }

        unset($_GET['id']);
        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if ($resp->code !== 400 || $resp->status !== 'error' || $resp->message !== 'param \'id\' is missing') {
            throw new Exception('error on testGetUser 4');
        }
    }

    public function testInsertUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        $req = new HTTPRequest();
        $resp = $this->controller->insertUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::insertUser\'') {
            throw new Exception('error on testInsertUser 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        
        $req = new HTTPRequest();
        $req->data['id'] = 'maria';
        $req->data['group'] = 'admin';

        $resp = $this->controller->insertUser($req);
        if ($resp->code !== 201 || $resp->status !== 'success' || $resp->message !== 'user created' || $resp->data['id'] !== 'maria' || $resp->data['group']['id'] !== 'admin') {
            print_r($resp);
            throw new Exception('error on testInsertUser 2');
        }

        $req->data = [];
        $resp = $this->controller->insertUser($req);
        if ($resp->code !== 400 || $resp->message !== 'key id not found in data') {
            throw new Exception('error on testInsertUser 3');
        }
        
        $req->data['id'] = 'maria';
        $resp = $this->controller->insertUser($req);
        if ($resp->code !== 400 || $resp->message !== 'key group not found in data') {
            print_r($resp);
            throw new Exception('error on testInsertUser 4');
        }
    }

    public function testUpdateUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateUser';
        $req = new HTTPRequest();
        $resp = $this->controller->updateUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::updateUser\'') {
            throw new Exception('error on testUpdateUser');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateUser';

        $req = new HTTPRequest();
        $req->data['id'] = 'maria';
        $req->data['group'] = 'admin';

        $resp = $this->controller->updateUser($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testUpdateUser');
        }

        $this->database->insertUser('maria', 'contributor');
        $resp = $this->controller->updateUser($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'user updated' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testUpdateUser');
        }

    }

    public function testGetGraph(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getGraph';
        $req = new HTTPRequest();
        $resp = $this->controller->getGraph($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::getGraph\'') {
            throw new Exception('error on testGetGraph');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getGraph';

        $req = new HTTPRequest();
        $resp = $this->controller->getGraph($req);
    }

    public function testGetNode(): void
    {
        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNode';
        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'DELETE\' not allowed in \'HTTPController::getNode\'') {
            throw new Exception('error on testGetNode 1');
        }

        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNode';

        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 404 || $resp->message !== 'node not found') {
            throw new Exception('error on testGetNode 2');
        }
        
        $this->database->insertNode('node1', 'label 1', 'application', 'service');
        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'node found') {
            throw new Exception('error on testGetNode 3');
        }

        $_GET = [];
        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'id\' is missing') {
            throw new Exception('error on testGetNode 4');
        }
    }

    public function testGetNodes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodes($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getNodes\'') {
            throw new Exception('error on testGetNodes');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';

        $req = new HTTPRequest();
        $resp = $this->controller->getNodes($req);
        if ($resp->code !== 200 || count($resp->data) > 0) {
            throw new Exception('error on testGetNodes');
        }

        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $req = new HTTPRequest();
        $resp = $this->controller->getNodes($req);
        if ($resp->code !== 200 || count($resp->data) !== 2) {
            throw new Exception('error on testGetNodes');
        }
        if ($resp->data[0]['id'] !== 'node1' || $resp->data[1]['id'] !== 'node2') {
            throw new Exception('error on testGetNodes');
        }
    }

    public function testGetNodeParentOf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeParentOf';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeParentOf($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getNodeParentOf\'') {
            print_r($resp);
            throw new Exception('error on testGetNodeParentOf');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeParentOf';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeParentOf($req);
        if ($resp->code !== 400 || $resp->status !== 'error') {
            print_r($resp);
            throw new Exception('error on testGetNodeParentOf');
        }

        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeParentOf';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeParentOf($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['id'] !== 'node1') {
            print_r($resp);
            throw new Exception('error on testGetNodeParentOf');
        }

        $_GET['id'] = 'node2';
        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $this->database->insertEdge('node1-node2', 'node1', 'node2', 'label');
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeParentOf($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->data['id'] !== 'node1') {
            print_r($resp);
            throw new Exception('error on testGetNodeParentOf');
        }
    }

    public function testGetDependentNodesOf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getDependentNodesOf';
        $req = new HTTPRequest();

        $resp = $this->controller->getDependentNodesOf($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getDependentNodesOf\'') {
            print_r($resp);
            throw new Exception('error on testGetDependentNodesOf');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getDependentNodesOf';
        $req = new HTTPRequest();
        $resp = $this->controller->getDependentNodesOf($req);
        if ($resp->code !== 400 || $resp->status !== 'error') {
            print_r($resp);
            throw new Exception('error on testGetDependentNodesOf');
        }

        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getDependentNodesOf';
        $req = new HTTPRequest();
        $resp = $this->controller->getDependentNodesOf($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) !== 0) {
            throw new Exception('error on testGetDependentNodesOf');
        }

        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $this->database->insertNode('node3', 'label3', 'application', 'service');
        $this->database->insertEdge('node1-node2', 'node1', 'node2', 'label');
        $this->database->insertEdge('node1-node3', 'node1', 'node3', 'label');

        $_GET['id'] = 'node1';
        $req = new HTTPRequest();
        $resp = $this->controller->getDependentNodesOf($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) !== 2) {
            throw new Exception('error on testGetDependentNodesOf');
        }

        if($resp->data[0]['id'] !== 'node2' || $resp->data[1]['id'] !== 'node3') {
            throw new Exception('error on testGetDependentNodesOf');
        }
    }

    public function testInsertNode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertNode';

        $req = new HTTPRequest();
        $resp = $this->controller->insertNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::insertNode\'') {
            throw new Exception('error on testInsertNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertNode';

        $req = new HTTPRequest();
        $req->data['id'] = 'node1';
        $req->data['label'] = 'node1';
        $req->data['category'] = 'application';
        $req->data['type'] = 'database';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->insertNode($req);
    }
    
    public function testUpdateNode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNode';
        $req = new HTTPRequest();
        $resp = $this->controller->updateNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::updateNode\'') {
            throw new Exception('error on testUpdateNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNode';
        
        $req = new HTTPRequest();
        $req->data['id'] = 'node1';
        $req->data['label'] = 'node1';
        $req->data['category'] = 'application';
        $req->data['type'] = 'database';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->updateNode($req);
    }
    
    public function testDeleteNode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteNode';
        $req = new HTTPRequest();
        $resp = $this->controller->deleteNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::deleteNode\'') {
            throw new Exception('error on testDeleteNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteNode';

        $req = new HTTPRequest();
        $req->data['id'] = 'node1';
        $req->data['label'] = 'node1';
        $req->data['category'] = 'application';
        $req->data['type'] = 'database';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->deleteNode($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['id'] !== 'node1') {
            throw new Exception('error on testDeleteNode');
        }
        $this->database->insertNode('node1', 'label 1', 'application', 'service');
        $req = new HTTPRequest();
        $req->data['id'] = 'node1';
        $resp = $this->controller->deleteNode($req);
        if ($resp->code !== 204 || $resp->status !== 'success' || $resp->data['id'] !== 'node1') {
            throw new Exception('error on testDeleteNode');
        }
    }

    public function testGetEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdge';
        $req = new HTTPRequest();
        $resp = $this->controller->getEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getEdge\'') {
            throw new Exception('error on testGetEdge 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdge';

        $_GET['source'] = 'node1';
        $_GET['target'] = 'node2';
        $req = new HTTPRequest();
        $resp = $this->controller->getEdge($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['source'] !== 'node1') {
            throw new Exception('error on testGetEdge 2');
        }

        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $this->database->insertEdge('node1-node2', 'node1', 'node2', 'label');
        
        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->getEdge($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->data['source'] !== 'node1') {
            throw new Exception('error on testGetEdge 3');
        }
    }
    
    public function testGetEdges(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdges';
        $req = new HTTPRequest();
        $resp = $this->controller->getEdges($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getEdges\'') {
            throw new Exception('error on testGetEdges');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdges';

        $req = new HTTPRequest();
        $resp = $this->controller->getEdges($req);
    }
    
    public function testInsertEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertEdge';
        $req = new HTTPRequest();
        $resp = $this->controller->insertEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::insertEdge\'') {
            throw new Exception('error on testInsertEdge');
        }

        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertEdge';

        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $req->data['label'] = 'edge from node1 to node2';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->insertEdge($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->data['source'] !== 'node1') {
            throw new Exception('error on testInsertEdge');
        }
    }
    
    public function testUpdateEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateEdge';
        $req = new HTTPRequest();
        $resp = $this->controller->updateEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::updateEdge\'') {
            throw new Exception('error on testUpdateEdge');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateEdge';
        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $req->data['label'] = 'edge from node1 to node2';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->updateEdge($req);
    }
    
    public function testDeleteEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteEdge';
        $req = new HTTPRequest();
        $resp = $this->controller->deleteEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::deleteEdge\'') {
            throw new Exception('error on testDeleteEdge');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteEdge';
        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->deleteEdge($req);
    }

    public function testGetStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getStatus($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::getStatus\'') {
            throw new Exception('error on testGetStatus');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getStatus($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) > 0) {
            throw new Exception('error on testGetStatus');
        }
        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');

        $req = new HTTPRequest();
        $resp = $this->controller->getStatus($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) !== 2) {
            throw new Exception('error on testGetStatus');
        }
        if ($resp->data[0]['node_id'] !== 'node1' || $resp->data[1]['node_id'] !== 'node2') {
            throw new Exception('error on testGetStatus');
        }
    }
    
    public function testGetNodeStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getNodeStatus\'') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 400 || $resp->status !== 'error' || $resp->message !== 'param \'node_id\' is missing') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 2');
        }

        $_GET[ModelStatus::STATUS_KEYNAME_NODE_ID] = 'node1';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 404 || $resp->message !== 'node not found' || $resp->data[ModelStatus::STATUS_KEYNAME_NODE_ID] !== 'node1') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 3');
        }

        $this->database->insertNode('node1', 'label 1', 'business', 'database');
        $_GET[ModelStatus::STATUS_KEYNAME_NODE_ID] = 'node1';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 200 || $resp->message !== 'node found' || $resp->data[ModelStatus::STATUS_KEYNAME_NODE_ID] !== 'node1' || $resp->data['status'] !== 'unknown') {
            throw new Exception('error on testGetNodeStatus');
        }
    }
    
    public function testUpdateNodeStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNodeStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->updateNodeStatus($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::updateNodeStatus\'') {
            throw new Exception('error on testUpdateNodeStatus');
        }

        $this->database->insertNode('node1', 'label', 'application', 'service');
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNodeStatus';
        $req = new HTTPRequest();
        $req->data['node_id'] = 'node1';
        $req->data['status'] = 'healthy';
        $resp = $this->controller->updateNodeStatus($req);
    }

    public function testGetLogs(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getLogs';
        $req = new HTTPRequest();
        $resp = $this->controller->getLogs($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getLogs\'') {
            throw new Exception('error on testGetLogs 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getLogs';
        $req = new HTTPRequest();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'limit\' is missing') {
            throw new Exception('error on testGetLogs 2');
        }
        
        $_GET['limit'] = 2;
        $req = new HTTPRequest();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 200 || $resp->message !== 'logs found') {
            throw new Exception('error on testGetLogs 3');
        }
        
        $this->database->insertLog('node', 'node1', 'insert', null, ['id' => 'node1'], 'user', '293820');
        sleep(1);
        $this->database->insertLog('node', 'node2', 'insert', null, ['id' => 'node2'], 'user', '111111');
        sleep(1);
        $this->database->insertLog('node', 'node3', 'insert', null, ['id' => 'node3'], 'user', '111111');
        
        $_GET['limit'] = 2;
        $req = new HTTPRequest();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 200 || $resp->message !== 'logs found') {
            throw new Exception('error on testGetLogs');
        }
    }
}

#####################################

class TestHTTPRequest extends TestAbstractTest
{
    public function testHTTPRequest()
    {
        $_GET['id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new HTTPRequest();
        if($req->getParam('id') != 1) {
            throw new Exception('problem on testHTTPRequest');
        }
    }
}
#####################################

class TestModelNode extends TestAbstractTest
{
    public function testNodeConstructor(): void
    {
        $node = new ModelNode('node1', 'Node 01', 'business', 'server', ['key' => 'value']);

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
            new ModelNode('invalid@id', 'Label', 'business', 'server', []);
            throw new Exception('test_Node problem - should throw exception for invalid ID');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Test validation - label too long
        try {
            new ModelNode('node2', str_repeat('a', 210000), 'business', 'server', []);
            throw new Exception('test_Node problem - should throw exception for long label');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
    }
}

#####################################

class TestHelperCytoscape extends TestAbstractTest
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

    public function testHelperCytoscape(): void
    {
        include __DIR__ . "/www/images/compiled_images.php";

        $img = new HelperImages($images);
        $cy = new HelperCytoscape($this->database, $img, 'http://example.com/images');

        $nodes = [
            new ModelNode('n1', 'Node 1', 'business', 'server', ['a' => 1]),
            new ModelNode('n2', 'Node 2', 'business', 'server', ['b' => 2]),
            new ModelNode('n3', 'Node 3', 'business', 'server', ['c' => 3]),
        ];

        $edges = [
            new ModelEdge('n1', 'n2', 'label1'),
            new ModelEdge('n2', 'n3', 'label2'),
        ];

        $graph = new ModelGraph($nodes, $edges);
        $data = $cy->toArray($graph);
        // print_r($data);
        // exit();
    }
}

#####################################

class TestModelGroup extends TestAbstractTest
{
    public function testGroupConstructor(): void
    {
        $group = new ModelGroup('contributor');
        $data = $group->toArray();
        if($data['id'] != $group->getId()) {
            throw new Exception('test_Group problem');
        }
    }

    public function testGroupException(): void
    {
        try {
            new ModelGroup('xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }
        throw new Exception('test_Group problem');
    }
}

#####################################

class TestModelStatus extends TestAbstractTest
{
    public function testStatusConstructor(): void
    {
        $status = new ModelStatus('node1', 'healthy');

        if($status->getNodeId() != 'node1' || $status->getStatus() != 'healthy') {
            throw new Exception('testStatusConstructor problem');
        }

        $data = $status->toArray();
        if($data['node_id'] != 'node1' || $data['status'] != 'healthy') {
            throw new Exception('testStatusConstructor problem');
        }
    }

    public function testStatusException(): void
    {
        try {
            new ModelStatus('node1', 'xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }

        throw new Exception(('problem on testStatusException'));
    }
}
#####################################

class TestHTTPBadRequestResponse extends TestAbstractTest
{
    public function testHTTPBadRequestResponse(): void
    {
        $resp = new HTTPBadRequestResponse('bad request', ['key' => 'val']);
        if($resp->code != 400) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "bad request") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}
#####################################

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
        $foundService = false;
        foreach ($types as $type) {
            if ($type->id === 'service') {
                $foundService = true;
                break;
            }
        }
        if (!$foundService) {
            throw new Exception('error on testGetTypes - service type not found');
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
        $node1 = new ModelNode('n1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('n2', 'Node 02', 'business', 'service', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge1 = new ModelEdge('n1', 'n2', 'label', ['weight' => '10']);
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
        $newNode = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
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
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $nodes = $this->service->getNodes();
        if (count($nodes) !== 0) {
            throw new Exception('error on testGetNodes - should be empty');
        }
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
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
        $nodeA = new ModelNode('nodeA', 'Node A', 'business', 'service', ['key' => 'valueA']);
        $nodeB = new ModelNode('nodeB', 'Node B', 'business', 'database', ['key' => 'valueB']);
        $nodeC = new ModelNode('nodeC', 'Node C', 'business', 'service', ['key' => 'valueC']);
        $this->service->insertNode($nodeA);
        $this->service->insertNode($nodeB);
        $this->service->insertNode($nodeC);
        $edgeAB = new ModelEdge('nodeA', 'nodeB', 'label', ['relation' => 'parent']);
        $edgeAC = new ModelEdge('nodeA', 'nodeC', 'label', ['relation' => 'parent']);
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
        $nodeA = new ModelNode('nodeA', 'Node A', 'business', 'service', ['key' => 'valueA']);
        $nodeB = new ModelNode('nodeB', 'Node B', 'business', 'database', ['key' => 'valueB']);
        $nodeC = new ModelNode('nodeC', 'Node C', 'business', 'service', ['key' => 'valueC']);
        $this->service->insertNode($nodeA);
        $this->service->insertNode($nodeB);
        $this->service->insertNode($nodeC);
        $edgeAB = new ModelEdge('nodeA', 'nodeB', 'label', ['relation' => 'parent']);
        $edgeAC = new ModelEdge('nodeA', 'nodeC', 'label', ['relation' => 'parent']);
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
        $node = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
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
        $node = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value']);
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

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
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
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = $this->service->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge - should be null');
        }
        $newEdge = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);
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
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new ModelNode('node3', 'Node 03', 'application', 'service', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);
        $edge1 = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);
        $edge2 = new ModelEdge('node2', 'node3', 'label', ['weight' => '20']);
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
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge->getId() !== 'node1-node2' || $retrievedEdge->getSource() !== 'node1' || $retrievedEdge->getTarget() !== 'node2') {
            throw new Exception('error on testInsertEdge');
        }
    }
    
    public function testUpdateEdge(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $node3 = new ModelNode('node3', 'Node 03', 'application', 'service', ['key' => 'value3']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->insertNode($node3);

        $edge = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);
        
        $updatedEdge = new ModelEdge('node1', 'node2', 'label', ['weight' => '30']);
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
        $nonExistentEdge = new ModelEdge('node1', 'node3', 'label', ['weight' => '50']);
        if ($this->service->updateEdge($nonExistentEdge)) {
            throw new Exception('error on testUpdateEdge - should return false for non-existent edge');
        }
    }
    
    public function testDeleteEdge(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $edge = new ModelEdge('node1', 'node2', 'label', ['weight' => '10']);
        $this->service->insertEdge($edge);
        $retrievedEdge = $this->service->getEdge('node1', 'node2');
        if ($retrievedEdge === null) {
            throw new Exception('error on testDeleteEdge - edge not inserted');
        }
        $this->service->deleteEdge(new ModelEdge('node1', 'node2', 'label'));
        $edges = $this->service->getEdges();
        if (count($edges) !== 0) {
            throw new Exception('error on testDeleteEdge - edge not deleted');
        }

        // Test deleting non-existent edge
        if ($this->service->deleteEdge(new ModelEdge('node1', 'node2', 'label'))) {
            throw new Exception('error on testDeleteEdge - should return false for non-existent edge');
        }
    }
    
    public function testGetStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $status = $this->service->getStatus();

        if (count($status) !== 0) {
            throw new Exception('error on testGetStatus - should be empty');
        }
        
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        $this->service->insertNode($node1);
        $this->service->insertNode($node2);
        $this->service->updateNodeStatus(new ModelStatus('node1', 'healthy'));
        $this->service->updateNodeStatus(new ModelStatus('node2', 'unhealthy'));
        
        $status = $this->service->getStatus();
        if (count($status) !== 2) {
            throw new Exception('error on testGetStatus - expected 2 status');
        }
    }
    
    public function testGetNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
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
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
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

        $node1 = new ModelNode('node1', 'Node 01', 'business', 'service', ['key' => 'value1']);
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

#####################################

class TestHTTPUnauthorizedResponse extends TestAbstractTest
{
    public function testHTTPUnauthorizedResponse(): void
    {
        $resp = new HTTPUnauthorizedResponse('database error', ['key' => 'val']);
        if($resp->code != 401) {
            throw new Exception('problem on code testHTTPUnauthorizedResponse 1');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPUnauthorizedResponse 2');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on testHTTPUnauthorizedResponse 3');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPUnauthorizedResponse 4');
        }
    }
}
#####################################

class TestModelLog extends TestAbstractTest
{
    public function testLogConstructor(): void
    {
        $oldData = ['id' => 'node1', 'label' => 'Old Label'];
        $newData = ['id' => 'node1', 'label' => 'New Label'];

        $log = new ModelLog('node', 'node1', 'update', $oldData, $newData);

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
        $log2 = new ModelLog('node', 'node2', 'insert', null, ['id' => 'node2']);
        if ($log2->oldData !== null) {
            throw new Exception('test_AuditLog problem - oldData should be null');
        }

        if ($log2->newData['id'] != 'node2') {
            throw new Exception('test_AuditLog problem - newData mismatch for insert');
        }

        // Test delete action with null newData
        $log3 = new ModelLog('edge', 'edge1', 'delete', ['id' => 'edge1'], null);
        if ($log3->newData !== null) {
            throw new Exception('test_AuditLog problem - newData should be null for delete');
        }

        if ($log3->oldData['id'] != 'edge1') {
            throw new Exception('test_AuditLog problem - oldData mismatch for delete');
        }
    }
}

#####################################

