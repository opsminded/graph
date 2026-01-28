<?php

declare(strict_types=1);

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

class TestHelperLogger extends TestAbstractTest
{
    public function testLoggerConstructor(): void
    {
        
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
        global $DATA_IMAGES;
        $img = new HelperImages($DATA_IMAGES);
        $cy = new HelperCytoscape($this->database, $img, 'http://example.com/images');

        $nodes = [
            new Node('n1', 'Node 1', 'business', 'server',  false, ['a' => 1]),
            new Node('n2', 'Node 2', 'business', 'server', false, ['b' => 2]),
            new Node('n3', 'Node 3', 'business', 'server', false, ['c' => 3]),
        ];

        $edges = [
            new Edge('n1', 'n2', 'label1'),
            new Edge('n2', 'n3', 'label2'),
        ];

        $graph = new Graph($nodes, $edges);
        $data = $cy->toArray($graph);
    }
}

#####################################

final class TestOKResponse extends TestAbstractTest
{
    public function testOKResponse(): void
    {
        $resp = new OKResponse('node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testOKResponse');
        }
    }
}
#####################################

class TestUnauthorizedResponse extends TestAbstractTest
{
    public function testUnauthorizedResponse(): void
    {
        $resp = new UnauthorizedResponse('database error', ['key' => 'val']);
        if($resp->code != 401) {
            throw new Exception('problem on code testUnauthorizedResponse 1');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testUnauthorizedResponse 2');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on testUnauthorizedResponse 3');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testUnauthorizedResponse 4');
        }
    }
}
#####################################

class TestNotFoundResponse extends TestAbstractTest
{
    public function testNotFoundResponse(): void
    {
        $resp = new NotFoundResponse('node not found', ['key' => 'val']);
        if($resp->code != 404) {
            throw new Exception('problem on code testNotFoundResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testNotFoundResponse');
        }

        if ($resp->message != "node not found") {
            throw new Exception('problem on testNotFoundResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testNotFoundResponse');
        }
    }
}
#####################################

final class TestController extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?ControllerInterface $controller;

    private ?HelperImages $imagesHelper;
    private ?HelperCytoscape $cytoscapeHelper;
    

    public function up(): void
    {
        global $DATA_IMAGES;

        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->imagesHelper = new HelperImages($DATA_IMAGES);
        
        $this->database = new Database($this->pdo, $this->databaseLogger);

        $this->cytoscapeHelper = new HelperCytoscape($this->database, $this->imagesHelper, 'http://example.com/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new Controller($this->service, $this->cytoscapeHelper, $this->controllerLogger);
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
        HelperContext::update('tester', 'admin', '127.0.0.1');
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getUser';
        $req = new Request();
        $resp = $this->controller->getUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getUser\'') {
            print_r($resp);
            throw new Exception('error on testGetUser 1');
        }
        
        $_GET['id'] = 'maria';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getUser';

        $req = new Request();
        $resp = $this->controller->getUser($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->message !== 'user not found') {
            throw new Exception('error on testGetUser 2');
        }

        $this->database->insertUser(new UserDTO('maria', 'contributor'));

        $req = new Request();
        $resp = $this->controller->getUser($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'user found' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testGetUser 3');
        }

        unset($_GET['id']);
        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->insertUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::insertUser\'') {
            throw new Exception('error on testInsertUser 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        
        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->updateUser($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::updateUser\'') {
            throw new Exception('error on testUpdateUser');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateUser';

        $req = new Request();
        $req->data['id'] = 'maria';
        $req->data['group'] = 'admin';

        $resp = $this->controller->updateUser($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testUpdateUser');
        }

        $this->database->insertUser(new UserDTO('maria', 'contributor'));
        $resp = $this->controller->updateUser($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'user updated' || $resp->data['id'] !== 'maria') {
            throw new Exception('error on testUpdateUser');
        }
    }

    public function testGetCategories(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getCategories';
        $req = new Request();

        $resp = $this->controller->getCategories($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'Controller::getCategories\'') {
            print_r($resp);
            throw new Exception('error on testGetCategories 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $req = new Request();
        $resp = $this->controller->getCategories($req);
        
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) === 0) {
            print_r($resp);
            throw new Exception('error on testGetCategories 2');
        }

        if ($resp->data[0]['id'] !== 'business' || $resp->data[1]['id'] !== 'application' || $resp->data[2]['id'] !== 'infrastructure') {
            print_r($resp);
            throw new Exception('error on testGetCategories 3');
        }
    }

    public function testGetTypes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getTypes';
        $req = new Request();

        $resp = $this->controller->getTypes($req);

        if ($resp->code != 405 || $resp->message != 'method \'DELETE\' not allowed in \'Controller::getTypes\'') {
            throw new Exception('error on testGetTypes');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getTypes';
        $req = new Request();
        $resp = $this->controller->getTypes($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) === 0) {
            throw new Exception('error on testGetTypes');
        }
        
        if ($resp->data[0]['id'] !== 'business' || $resp->data[1]['id'] !== 'business_case') {
            throw new Exception('error on testGetTypes');
        }
    }

    public function testGetNode(): void
    {
        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNode';
        $req = new Request();
        $resp = $this->controller->getNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'DELETE\' not allowed in \'Controller::getNode\'') {
            throw new Exception('error on testGetNode 1');
        }

        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNode';

        $req = new Request();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 404 || $resp->message !== 'node not found') {
            throw new Exception('error on testGetNode 2');
        }
        
        $this->database->insertNode(new NodeDTO('node1', 'label 1', 'application', 'service', true, []));
        $req = new Request();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->message !== 'node found') {
            throw new Exception('error on testGetNode 3');
        }

        $_GET = [];
        $req = new Request();
        $resp = $this->controller->getNode($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'id\' is missing') {
            throw new Exception('error on testGetNode 4');
        }
    }

    public function testGetNodes(): void
    {
        $this->pdo->exec('delete from nodes');
        
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new Request();
        $resp = $this->controller->getNodes($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getNodes\'') {
            throw new Exception('error on testGetNodes 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';

        $req = new Request();
        $resp = $this->controller->getNodes($req);
        if ($resp->code !== 200 || count($resp->data) > 0) {
            print_r($resp);
            exit();
            throw new Exception('error on testGetNodes 2');
        }

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', true, []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', true, []));
        $req = new Request();
        $resp = $this->controller->getNodes($req);
        if ($resp->code !== 200 || count($resp->data) !== 2) {
            throw new Exception('error on testGetNodes 3');
        }
        if ($resp->data[0]['id'] !== 'node1' || $resp->data[1]['id'] !== 'node2') {
            throw new Exception('error on testGetNodes 4');
        }
    }

    public function testInsertNode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertNode';

        $req = new Request();
        $resp = $this->controller->insertNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'Controller::insertNode\'') {
            throw new Exception('error on testInsertNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertNode';

        $req = new Request();
        $req->data['id'] = 'node1';
        $req->data['label'] = 'node1';
        $req->data['category'] = 'application';
        $req->data['type'] = 'database';
        $req->data['user_created'] = true;
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->insertNode($req);
    }
    
    public function testUpdateNode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNode';
        $req = new Request();
        $resp = $this->controller->updateNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::updateNode\'') {
            throw new Exception('error on testUpdateNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNode';
        
        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->deleteNode($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::deleteNode\'') {
            throw new Exception('error on testDeleteNode');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteNode';

        $req = new Request();
        $req->data['id'] = 'node1';
        $req->data['label'] = 'node1';
        $req->data['category'] = 'application';
        $req->data['type'] = 'database';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->deleteNode($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['id'] !== 'node1') {
            throw new Exception('error on testDeleteNode');
        }
        $this->database->insertNode(new NodeDTO('node1', 'label 1', 'application', 'service', true, []));
        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->getEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getEdge\'') {
            throw new Exception('error on testGetEdge 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdge';

        $_GET['source'] = 'node1';
        $_GET['target'] = 'node2';
        $req = new Request();
        $resp = $this->controller->getEdge($req);
        if ($resp->code !== 404 || $resp->status !== 'error' || $resp->data['source'] !== 'node1') {
            throw new Exception('error on testGetEdge 2');
        }

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', true, []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', true, []));
        $this->database->insertEdge(new EdgeDTO('node1-node2', 'node1', 'node2', 'label', []));
        
        $req = new Request();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->getEdge($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || $resp->data['source'] !== 'node1') {
            throw new Exception('error on testGetEdge 3');
        }
    }
    
    public function testGetEdges(): void
    {
        $this->pdo->exec('delete from nodes');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdges';
        $req = new Request();
        $resp = $this->controller->getEdges($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getEdges\'') {
            throw new Exception('error on testGetEdges 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdges';

        $req = new Request();
        $resp = $this->controller->getEdges($req);

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', true, []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', true, []));
        $this->database->insertEdge(new EdgeDTO('node1-node2', 'node1', 'node2', 'label', []));

        $req = new Request();
        $resp = $this->controller->getEdges($req);

        if ($resp->code !== 200 || count($resp->data) !== 1) {
            print_r($resp->data);
            exit();
            throw new Exception('error on testGetEdges 2');
        }
        if ($resp->data[0]['source'] !== 'node1' || $resp->data[0]['target'] !== 'node2') {
            throw new Exception('error on testGetEdges 3');
        }
        
    }
    
    public function testInsertEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertEdge';
        $req = new Request();
        $resp = $this->controller->insertEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::insertEdge\'') {
            throw new Exception('error on testInsertEdge');
        }

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', true, []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', true, []));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertEdge';

        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->updateEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::updateEdge\'') {
            throw new Exception('error on testUpdateEdge');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateEdge';
        $req = new Request();
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
        $req = new Request();
        $resp = $this->controller->deleteEdge($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'Controller::deleteEdge\'') {
            throw new Exception('error on testDeleteEdge');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteEdge';
        $req = new Request();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->deleteEdge($req);
    }

    // public function testGetStatus(): void
    // {
    //     $_SERVER['REQUEST_METHOD'] = 'PUT';
    //     $_SERVER['SCRIPT_NAME'] = 'api.php';
    //     $_SERVER['REQUEST_URI'] = 'api.php/getStatus';
    //     $req = new Request();
    //     $resp = $this->controller->getStatus($req);
    //     if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'Controller::getStatus\'') {
    //         throw new Exception('error on testGetStatus');
    //     }

    //     $_SERVER['REQUEST_METHOD'] = 'GET';
    //     $_SERVER['SCRIPT_NAME'] = 'api.php';
    //     $_SERVER['REQUEST_URI'] = 'api.php/getStatus';
    //     $req = new Request();
    //     $resp = $this->controller->getStatus($req);
    //     if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) > 0) {
    //         throw new Exception('error on testGetStatus');
    //     }
    //     $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', true, []));
    //     $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', true, []));

    //     $req = new Request();
    //     $resp = $this->controller->getStatus($req);
    //     if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) !== 2) {
    //         throw new Exception('error on testGetStatus');
    //     }
    //     if ($resp->data[0]['node_id'] !== 'node1' || $resp->data[1]['node_id'] !== 'node2') {
    //         throw new Exception('error on testGetStatus');
    //     }
    // }
    
    public function testGetNodeStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeStatus';
        $req = new Request();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getNodeStatus\'') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeStatus';
        $req = new Request();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 400 || $resp->status !== 'error' || $resp->message !== 'param \'node_id\' is missing') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 2');
        }

        $_GET[Status::STATUS_KEYNAME_NODE_ID] = 'node1';
        $req = new Request();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 404 || $resp->message !== 'node not found' || $resp->data[Status::STATUS_KEYNAME_NODE_ID] !== 'node1') {
            print_r($resp);
            throw new Exception('error on testGetNodeStatus 3');
        }

        $this->database->insertNode(new NodeDTO('node1', 'label 1', 'business', 'database', true, []));
        $_GET[Status::STATUS_KEYNAME_NODE_ID] = 'node1';
        $req = new Request();
        $resp = $this->controller->getNodeStatus($req);
        if ($resp->code !== 200 || $resp->message !== 'node found' || $resp->data[Status::STATUS_KEYNAME_NODE_ID] !== 'node1' || $resp->data['status'] !== 'unknown') {
            throw new Exception('error on testGetNodeStatus');
        }
    }
    
    public function testUpdateNodeStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNodeStatus';
        $req = new Request();
        $resp = $this->controller->updateNodeStatus($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::updateNodeStatus\'') {
            throw new Exception('error on testUpdateNodeStatus');
        }

        $this->database->insertNode(new NodeDTO('node1', 'label', 'application', 'service', true, []));
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateNodeStatus';
        $req = new Request();
        $req->data['node_id'] = 'node1';
        $req->data['status'] = 'healthy';
        $resp = $this->controller->updateNodeStatus($req);
    }

    public function testGetProject(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $req = new Request();
        $resp = $this->controller->getProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getProject\'') {
            throw new Exception('error on testGetProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $req = new Request();
        $resp = $this->controller->getProject($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'id\' is missing') {
            throw new Exception('error on testGetProject 2');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $_GET['id'] = 'meu-project';
        $req = new Request();
        $resp = $this->controller->getProject($req);
        if( $resp->code !== 404 || $resp->message !== 'project not found') {
            print_r($resp);
            throw new Exception('error on testGetProject 3');
        }
        
        $this->database->insertProject(new ProjectDTO('meu-project', 'meu project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null, ['nodes' => ['a', 'b']]));
        
        $req = new Request();
        $resp = $this->controller->getProject($req);
        if( $resp->code !== 200 || $resp->message !== 'project found') {
            throw new Exception('error on testGetProject 4');
        }
        if($resp->data['id'] !== 'meu-project' || $resp->data['name'] !== 'meu project') {
            throw new Exception('error on testGetProject 5');
        }
    }

    public function testGetProjects(): void
    {
        $this->pdo->exec('delete from projects');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProjects';
        $req = new Request();
        $resp = $this->controller->getProjects($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getProjects\'') {
            throw new Exception('error on testGetProjects 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProjects';
        $req = new Request();
        $resp = $this->controller->getProjects($req);
        if( $resp->code !== 200 || $resp->message !== 'projects found' || count($resp->data) !== 0) {
            throw new Exception('error on testGetProjects 2');
        }

        $this->database->insertProject(new ProjectDTO('meu-project', 'meu project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null, ['nodes' => ['a', 'b']]));
        
        $req = new Request();
        $resp = $this->controller->getProjects($req);
        if( $resp->code !== 200 || $resp->message !== 'projects found' || count($resp->data) !== 1) {
            throw new Exception('error on testGetProjects 3');
        }
        if($resp->data[0]['id'] !== 'meu-project' || $resp->data[0]['name'] !== 'meu project') {
            throw new Exception('error on testGetProjects 4');
        }
    }

    public function testInsertProject(): void
    {
        $this->pdo->exec('delete from projects');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertProject';
        $req = new Request();
        $resp = $this->controller->insertProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::insertProject\'') {
            throw new Exception('error on testInsertProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertProject';
        $req = new Request();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1';
        $req->data['creator'] = 'admin';
        $req->data['nodes'] = ['a', 'b'];
        $resp = $this->controller->insertProject($req);
        if($resp->code !== 201 || $resp->message !== 'project created') {
            print_r($resp);
            throw new Exception('error on testInsertProject 2');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProjects';
        $req = new Request();
        $resp = $this->controller->getProjects($req);
        if( $resp->code !== 200 || $resp->message !== 'projects found' || count($resp->data) !== 1) {
            throw new Exception('error on testInsertProject 3');
        }

        if($resp->data[0]['name'] !== 'My Project 1') {
            throw new Exception('error on testInsertProject 4');
        }
    }

    public function testUpdateProject(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateProject';
        $req = new Request();
        $resp = $this->controller->updateProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::updateProject\'') {
            throw new Exception('error on testUpdateProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateProject';
        $req = new Request();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1 Updated';
        $req->data['creator'] = 'admin';
        $req->data['nodes'] = [];
        $resp = $this->controller->updateProject($req);
        if ($resp->code !== 404 || $resp->message !== 'project not updated' || $resp->data['id'] !== 'project1') {
            print_r($resp);
            throw new Exception('error on testUpdateProject 2');
        }

        $this->database->insertProject(new ProjectDTO('project1', 'My Project 1', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null, ['nodes' => [], 'edges' => []]));
        $req = new Request();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1 Updated';
        $req->data['creator'] = 'admin';
        $req->data['nodes'] = ['node1', 'node2'];
        $resp = $this->controller->updateProject($req);
        
        if ($resp->code !== 200 || $resp->message !== 'project updated' || $resp->data['name'] !== 'My Project 1 Updated') {
            throw new Exception('error on testUpdateProject 3');
        }
    }

    public function testDeleteProject(): void
    {
        $this->pdo->exec('delete from projects');
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteProject';
        $req = new Request();
        $resp = $this->controller->deleteProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'Controller::deleteProject\'') {
            throw new Exception('error on testDeleteProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteProject';
        $req = new Request();
        $req->data['id'] = 'project1';
        $resp = $this->controller->deleteProject($req);
        if ($resp->code !== 404 || $resp->message !== 'project not deleted' || $resp->data['id'] !== 'project1') {
            print_r($resp);
            throw new Exception('error on testDeleteProject 2');
        }

        $this->database->insertProject(new ProjectDTO('project1', 'My Project 1', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null, ['nodes' => [], 'edges' => []]));
        $req = new Request();
        $req->data['id'] = 'project1';
        $resp = $this->controller->deleteProject($req);
        if ($resp->code !== 204 || $resp->message !== 'project deleted' || $resp->data['id'] !== 'project1') {
            throw new Exception('error on testDeleteProject 3');
        }

        $projects = $this->database->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on testDeleteProject 4');
        }
    }

    public function testGetLogs(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getLogs';
        $req = new Request();
        $resp = $this->controller->getLogs($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'Controller::getLogs\'') {
            throw new Exception('error on testGetLogs 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getLogs';
        $req = new Request();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'limit\' is missing') {
            throw new Exception('error on testGetLogs 2');
        }
        
        $_GET['limit'] = 2;
        $req = new Request();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 200 || $resp->message !== 'logs found') {
            throw new Exception('error on testGetLogs 3');
        }
        
        $this->database->insertLog(new LogDTO('node', 'node1', 'insert', null, ['id' => 'node1'], 'user', '293820', new DateTimeImmutable()));
        sleep(1);
        $this->database->insertLog(new LogDTO('node', 'node2', 'insert', null, ['id' => 'node2'], 'user', '111111', new DateTimeImmutable()));
        sleep(1);
        $this->database->insertLog(new LogDTO('node', 'node3', 'insert', null, ['id' => 'node3'], 'user', '111111', new DateTimeImmutable()));
        
        $_GET['limit'] = 2;
        $req = new Request();
        $resp = $this->controller->getLogs($req);
        if ($resp->code !== 200 || $resp->message !== 'logs found') {
            throw new Exception('error on testGetLogs');
        }
    }
}

#####################################

class TestForbiddenResponse extends TestAbstractTest
{
    public function testForbiddenResponse(): void
    {
        $resp = new ForbiddenResponse('node not created', ['key' => 'val']);
        if($resp->code != 403) {
            throw new Exception('problem on code testForbiddenResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testForbiddenResponse');
        }

        if ($resp->message != "node not created") {
            throw new Exception('problem on testForbiddenResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testForbiddenResponse');
        }
    }
}
#####################################

class TestRequestRouter extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?ControllerInterface $controller;

    private ?HelperImages $imagesHelper;
    private ?HelperCytoscape $cytoscapeHelper;

    private ?RequestRouter $router;

    public function up(): void
    {
        global $DATA_IMAGES;

        $_GET = [];
        $_SERVER = [];

        $this->pdo = Database::createConnection('sqlite::memory:');

        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->database = new Database($this->pdo, $this->databaseLogger);

        $this->imagesHelper = new HelperImages($DATA_IMAGES);
        $this->cytoscapeHelper = new HelperCytoscape($this->database, $this->imagesHelper, 'http://localhost/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new Controller($this->service, $this->cytoscapeHelper, $this->controllerLogger);
        $this->router = new RequestRouter($this->controller);
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

    public function testRequestRouter(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        $req = new Request();
        $req->data[User::USER_KEYNAME_ID] = 'joao';
        $req->data[User::USER_KEYNAME_GROUP] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 201 || $resp->message !== 'user created' || $resp->data['id'] !== 'joao' || $resp->data['group']['id'] !== 'contributor') {
            print_r($resp);
            throw new Exception('error on testRequestRouter');
        }
    }

    public function testRequestRouterException(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/up';

        $req = new Request();
        $req->data[User::USER_KEYNAME_ID] = 'joao';
        $req->data[User::USER_KEYNAME_GROUP] = 'contributor';

        $resp = $this->router->handle($req);
        if($resp->code !== 500 || $resp->message !== 'method not found in list') {
            print_r($resp);
            exit();
            throw new Exception('error on testRequestRouterException');
        }
    }
}
#####################################

class TestBadRequestResponse extends TestAbstractTest
{
    public function testBadRequestResponse(): void
    {
        $resp = new BadRequestResponse('bad request', ['key' => 'val']);
        if($resp->code != 400) {
            throw new Exception('problem on code testBadRequestResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testBadRequestResponse');
        }

        if ($resp->message != "bad request") {
            throw new Exception('problem on testBadRequestResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testBadRequestResponse');
        }
    }
}
#####################################

class TestResponse extends TestAbstractTest
{
    public function testResponse(): void
    {
        $resp = new Response(200, 'success', 'node created', ['key' => 'val'], 'text/plain', 'dGVzdGU=', ['Content-Type' => 'application/json'], 'template');
        if($resp->code != 200) {
            throw new Exception('problem on testResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testResponse');
        }

        if ($resp->data['key'] != 'val') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->contentType != 'text/plain') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->headers['Content-Type'] != 'application/json') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->binaryContent != 'dGVzdGU=') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->template != 'template') {
            throw new Exception('problem on testResponse');
        }   
    }
}
#####################################

class TestRequestException extends TestAbstractTest
{
    public function testRequestException(): void
    {
        $req = new RequestException('message', ['key' => 'val'], ['id' => ''], '/get');
        if($req->getData() != ['key' => 'val']) {
            throw new Exception('problem on TestRequestException');
        }

        if($req->getParams() != ['id' => '']) {
            throw new Exception('problem on TestRequestException');
        }

        if($req->getPath() != '/get') {
            throw new Exception('problem on TestRequestException');
        }
    }
}
#####################################

class TestInternalServerErrorResponse extends TestAbstractTest
{
    public function testInternalServerErrorResponse(): void
    {
        $resp = new InternalServerErrorResponse('database error', ['key' => 'val']);
        if($resp->code != 500) {
            throw new Exception('problem on code TestInternalServerErrorResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status TestInternalServerErrorResponse');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on TestInternalServerErrorResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestInternalServerErrorResponse');
        }
    }
}
#####################################

class TestRequest extends TestAbstractTest
{
    public function testRequest(): void
    {
        $_GET['id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new Request();
        if($req->getParam('id') != 1) {
            throw new Exception('problem on testRequest');
        }
    }
}
#####################################

class TestCreatedResponse extends TestAbstractTest
{
    public function testCreatedResponse(): void
    {
        $resp = new CreatedResponse('node created', ['key' => 'val']);
        if($resp->code != 201) {
            throw new Exception('problem on code TestCreatedResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on status TestCreatedResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on TestCreatedResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestCreatedResponse');
        }
    }
}
#####################################

class TestNode extends TestAbstractTest
{
    public function testNodeConstructor(): void
    {
        $node = new Node('node1', 'Node 01', 'business', 'server', true, ['key' => 'value']);

        if ($node->getId() != 'node1' || $node->getLabel() != 'Node 01' || $node->getCategory() != 'business' || $node->getType() != 'server' || $node->getUserCreated() !== true) {
            throw new Exception('test_Node problem - property mismatch');
        }

        $data = $node->getData();
        if ($data['key'] != 'value') {
            throw new Exception('test_Node problem - data mismatch');
        }

        $data = $node->toArray();
        if ($data['id'] != 'node1' || $data['label'] != 'Node 01' || $data['category'] != 'business' || $data['type'] != 'server' || $data['user_created'] !== true) {
            throw new Exception('test_Node problem - toArray mismatch');
        }

        if ($data['data']['key'] != 'value') {
            throw new Exception('test_Node problem - toArray data mismatch');
        }

        // Test validation - invalid ID
        try {
            new Node('invalid@id', 'Label', 'business', 'server', true, []);
            throw new Exception('test_Node problem - should throw exception for invalid ID');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Test validation - label too long
        try {
            new Node('node2', str_repeat('a', 210000), 'business', 'server', true, []);
            throw new Exception('test_Node problem - should throw exception for long label');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
    }
}

#####################################

class TestLog extends TestAbstractTest
{
    public function testLogConstructor(): void
    {
        $oldData = ['id' => 'node1', 'label' => 'Old Label'];
        $newData = ['id' => 'node1', 'label' => 'New Label'];

        $log = new Log('node', 'node1', 'update', $oldData, $newData, 'user123', 'ip_address', new DateTimeImmutable());

        if ($log->getEntityType() != 'node' || $log->getEntityId() != 'node1' || $log->getAction() != 'update') {
            throw new Exception('test_AuditLog problem - property mismatch');
        }

        if ($log->getOldData()['label'] != 'Old Label') {
            throw new Exception('test_AuditLog problem - oldData mismatch');
        }

        if ($log->getNewData()['label'] != 'New Label') {
            throw new Exception('test_AuditLog problem - newData mismatch');
        }

        // Test with null data
        $log2 = new Log('node', 'node2', 'insert', null, ['id' => 'node2'], 'user123', 'ip_address', new DateTimeImmutable());
        if ($log2->getOldData() !== null) {
            throw new Exception('test_AuditLog problem - oldData should be null');
        }

        if ($log2->getNewData()['id'] != 'node2') {
            throw new Exception('test_AuditLog problem - newData mismatch for insert');
        }

        // Test delete action with null newData
        $log3 = new Log('edge', 'edge1', 'delete', ['id' => 'edge1'], null, 'user123', 'ip_address', new DateTimeImmutable());
        if ($log3->getNewData() !== null) {
            throw new Exception('test_AuditLog problem - newData should be null for delete');
        }

        if ($log3->getOldData()['id'] != 'edge1') {
            throw new Exception('test_AuditLog problem - oldData mismatch for delete');
        }
    }
}

#####################################

class TestUser extends TestAbstractTest
{
    public function testUserConstructor(): void
    {
        $user = new User('admin', new Group('admin'));
        $data = $user->toArray();
        if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
            throw new Exception('test_User problem');
        }
    }
}

#####################################

class TestGraph extends TestAbstractTest
{
    public function testGraphConstructor(): void
    {
        $node1 = new Node('node1', 'Node 01', 'business', 'server', false,  ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
        
        $edge1 = new Edge('node1', 'node2', 'lbl node1', ['weight' => '10']);

        $graph = new Graph([$node1, $node2], [$edge1]);
        
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

class TestStatus extends TestAbstractTest
{
    public function testStatusConstructor(): void
    {
        $status = new Status('node1', 'healthy');

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
            new Status('node1', 'xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }

        throw new Exception(('problem on testStatusException'));
    }
}
#####################################

class TestEdge extends TestAbstractTest
{
    public function testEdgeConstruct(): void
    {
        $edge = new Edge('node1', 'node2', 'label', ['weight' => '10']);

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
        $edge3 = new Edge('node5', 'node6', 'label');
        if (count($edge3->getData()) != 0) {
            throw new Exception('testEdgeConstruct');
        }
    }
}

#####################################

class TestGroup extends TestAbstractTest
{
    public function testGroupConstructor(): void
    {
        $group = new Group('contributor');
        $data = $group->toArray();
        if($data['id'] != $group->getId()) {
            throw new Exception('test_Group problem');
        }
    }

    public function testGroupException(): void
    {
        try {
            new Group('xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }
        throw new Exception('test_Group problem');
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
        $this->logger = new Logger(1);
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
        if ($user->id !== 'admin' || $user->group !== 'admin') {
            print_r($user);
            throw new Exception('admin expected');
        }
    }

    public function testGetUsers(): void
    {
        $users = $this->database->getUsers();
        if (count($users) !== 1) {
            throw new Exception('should have one user');
        }
        if ($users[0]->id !== 'admin' || $users[0]->group !== 'admin') {
            throw new Exception('admin expected');
        }
    }

    public function testInsertUser(): void
    {
        $this->database->insertUser(new UserDTO('maria', 'contributor'));
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();

        if ($user['id'] !== 'maria' || $user['user_group'] !== 'contributor') {
            throw new Exception('maria expected');
        }
        try {
            $this->database->insertUser(new UserDTO('maria', 'contributor'));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert user - user already exists. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error expected');
    }

    public function testBatchInsertUsers(): void
    {
        $users = [
            ['id' => 'joao', 'group' => 'admin'],
            ['id' => 'ana', 'group' => 'contributor'],
            ['id' => 'carlos', 'group' => 'viewer'],
            ['id' => 'joao', 'group' => 'admin'],
        ];

        try {
            $this->database->batchInsertUsers($users);
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert user in batch: user already exists: joao. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.id") {
                throw new Exception('unique constraint expected');
            }
        }

        $users = [
            ['id' => 'j', 'group' => 'admin'],
            ['id' => 'a', 'group' => 'contributor'],
            ['id' => 'c', 'group' => 'viewer'],
        ];

        $this->database->batchInsertUsers($users);

        foreach ($users as $u) {
            $stmt = $this->pdo->prepare('select * from users where id = :id');
            $stmt->execute([':id' => $u['id']]);
            $user = $stmt->fetch();

            if ($user['id'] !== $u['id'] || $user['user_group'] !== $u['group']) {
                throw new Exception($u['id'] . ' expected');
            }
        }
    }

    public function testUpdateUser(): void
    {
        $stmt = $this->pdo->prepare('insert into users (id, user_group) values (:id, :user_group)');
        $stmt->execute([':id' => 'maria', ':user_group' => 'contributor']);
        
        $this->database->updateUser(new UserDTO('maria', 'admin'));
        
        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();
        
        if ($user['id'] !== 'maria' || $user['user_group'] !== 'admin') {
            throw new Exception('expected maria admin');
        }
        if ($this->database->updateUser(new UserDTO('joao', 'contributor'))) {
            throw new Exception('expected joao not found');
        }
    }

    public function testDeleteUser(): void
    {
        $stmt = $this->pdo->prepare('insert into users (id, user_group) values (:id, :user_group)');
        $stmt->execute([':id' => 'maria', ':user_group' => 'contributor']);
        
        if (!$this->database->deleteUser('maria')) {
            throw new Exception('expected maria deleted');
        }

        $stmt = $this->pdo->prepare('select * from users where id = :id');
        $stmt->execute([':id' => 'maria']);
        $user = $stmt->fetch();

        if ($user !== false) {
            throw new Exception('expected maria not found');
        }

        if ($this->database->deleteUser('joao')) {
            throw new Exception('expected joao not found');
        }
    }

    public function testGetCategory(): void
    {
        $category = $this->database->getCategory('nonexistent');
        if ($category !== null) {
            throw new Exception('should return null');
        }
        $category = $this->database->getCategory('business');
        if ($category->id !== 'business' || $category->name !== 'Negócios') {
            throw new Exception('business expected');
        }
    }


    public function testGetCategories(): void
    {
        $categories = $this->database->getCategories();
        $originalCount = count($categories);
        
        if ($originalCount === 0) {
            throw new Exception('should have categories');
        }

        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 80, 80)');
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat2", "Category 2", "box", 80, 80)');

        $categories = $this->database->getCategories();
        if (count($categories) !== $originalCount + 2) {
            throw new Exception('should be two categories');
        }

        foreach ($categories as $key => $cat) {
            if ($cat->id !== 'cat1' && $cat->id !== 'cat2') {
                unset($categories[$key]);
            }
        }

        $categories = array_values($categories);


        if ($categories[0]->id !== 'cat1' || $categories[0]->name !== 'Category 1') {
            throw new Exception('error on category cat1');
        }

        if ($categories[1]->id !== 'cat2' || $categories[1]->name !== 'Category 2') {
            throw new Exception('error on category cat2');
        }
    }

    public function testInsertCategory(): void
    {
        $this->database->insertCategory(new CategoryDTO('cat1', 'Category 1', 'box', 100, 50));
        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();
        if ($category['id'] !== 'cat1' || $category['name'] !== 'Category 1' || $category['shape'] !== 'box' || $category['width'] !== 100 || $category['height'] !== 50) {
            throw new Exception('error on insert category cat1');
        }

        try {
            $this->database->insertCategory(new CategoryDTO('cat1', 'Category 1', 'box', 100, 50));
        } catch(DatabaseException $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert category - category already exists: cat1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: categories.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error expected');
    }

    public function testUpdateCategory(): void
    {
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->database->updateCategory(new CategoryDTO('cat1', 'Updated Category 1', 'circle', 150, 75));

        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();

        if ($category['id'] !== 'cat1' || $category['name'] !== 'Updated Category 1' || $category['shape'] !== 'circle' || $category['width'] !== 150 || $category['height'] !== 75) {
            throw new Exception('error on update category cat1');
        }

        $result = $this->database->updateCategory(new CategoryDTO('nonexistent', 'Name', 'box', 100, 50));
        if ($result) {
            throw new Exception('error on update nonexistent category');
        }

    }

    public function testDeleteCategory(): void
    {
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->database->deleteCategory('cat1');

        $stmt = $this->pdo->prepare('select * from categories where id = :id');
        $stmt->execute([':id' => 'cat1']);
        $category = $stmt->fetch();

        if ($category !== false) {
            throw new Exception('category cat1 should be deleted');
        }

        $result = $this->database->deleteCategory('nonexistent');
        if ($result) {
            throw new Exception('deleting nonexistent category should return false');
        }
    }

    public function testGetType(): void
    {
        $type = $this->database->getType('service');
        if ($type->id !== 'service' || $type->name !== 'Serviço') {
            throw new Exception('service expected');
        }

        $type = $this->database->getType('nonexistent');
        if ($type !== null) {
            throw new Exception('should return null');
        }
    }

    public function testGetTypes(): void
    {
        $types = $this->database->getTypes();
        $originalCount = count($types);
        
        if ($originalCount === 0) {
            throw new Exception('should have types');
        }

        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->pdo->exec('insert into types (id, name) values ("type2", "Type 2")');

        $types = $this->database->getTypes();
        if (count($types) !== $originalCount + 2) {
            throw new Exception('should be two types');
        }

        foreach ($types as $key => $type) {
            if ($type->id !== 'type1' && $type->id !== 'type2') {
                unset($types[$key]);
            }
        }

        $types = array_values($types);

        if ($types[0]->id !== 'type1' || $types[0]->name !== 'Type 1') {
            throw new Exception('error on type type1');
        }

        if ($types[1]->id !== 'type2' || $types[1]->name !== 'Type 2') {
            throw new Exception('error on type type2');
        }
    }

    public function testInsertType(): void
    {
        $this->database->insertType(new TypeDTO('type1', 'Type 1'));
        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();
        if ($type['id'] !== 'type1' || $type['name'] !== 'Type 1') {
            throw new Exception('error on insert type type1');
        }

        try {
            $this->database->insertType(new TypeDTO('type1', 'Type 1'));
        } catch(DatabaseException $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert type. Type already exists: type1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: types.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
    }

    public function testUpdateType(): void
    {
        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->database->updateType(new TypeDTO('type1', 'Updated Type 1'));

        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();

        if ($type['id'] !== 'type1' || $type['name'] !== 'Updated Type 1') {
            throw new Exception('error on update type type1');
        }

        $result = $this->database->updateType(new TypeDTO('nonexistent', 'Name'));
        if ($result) {
            throw new Exception('error on update nonexistent type');
        }
    }

    public function testDeleteType(): void
    {
        $this->pdo->exec('insert into types (id, name) values ("type1", "Type 1")');
        $this->database->deleteType('type1');

        $stmt = $this->pdo->prepare('select * from types where id = :id');
        $stmt->execute([':id' => 'type1']);
        $type = $stmt->fetch();

        if ($type !== false) {
            throw new Exception('type type1 should be deleted');
        }

        $result = $this->database->deleteType('nonexistent');
        if ($result) {
            throw new Exception('deleting nonexistent type should return false');
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
        
        if ($node->id !== 'node1' || $node->label !== 'Node 01' || $node->category !== 'business' || $node->type !== 'service') {
            throw new Exception('error on getNode');
        }

        if ($node->data['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if (!is_null($this->database->getNode('node2'))) {
            throw new Exception('null expected');
        }
    }

    public function testGetNodes(): void {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat1", "Category 1", "box", 100, 50)');
        $this->pdo->exec('insert into categories (id, name, shape, width, height) values ("cat2", "Category 2", "box", 100, 50)');
        $this->pdo->exec('insert into types (id, name) values ("app", "Application")');
        $this->pdo->exec('insert into types (id, name) values ("db", "Database")');
        
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');

        $stmt->execute([
            ':id' => 'node1',
            ':label' => 'Node 01',
            ':category' => 'cat1',
            ':type' => 'app',
            ':data' => json_encode(['running_on' => 'SRV01OP'])
        ]);
        usleep(500);
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

        if ($nodes[0]->id !== 'node1') {
            throw new Exception('error on getNode');
        }

        if ($nodes[0]->data['running_on'] !== 'SRV01OP') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]->id !== 'node2') {
            throw new Exception('error on getNode');
        }

        if ($nodes[1]->data['running_on'] !== 'SRV011P') {
            throw new Exception('error on getNode');
        }
    }

    public function testInsertNode(): void {
        $this->database->insertNode(new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']));
        

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node1']);
        $dbNode = $stmt->fetch();

        if ($dbNode['id'] !== 'node1' || $dbNode['label'] !== 'Node 01' || $dbNode['category'] !== 'business' || $dbNode['type'] !== 'service') {
            throw new Exception('error on testInsertNode');
        }
        if ($dbNode['data'] !== "{\"running_on\":\"SRV01OP\"}") {
            throw new Exception('error on testInsertNode');
        }

        $this->database->insertNode(new NodeDTO('user_created', 'User Created', 'application', 'database', true, ['created_by' => 'admin']));
        
        $stmt->execute([':id' => 'user_created']);
        $dbNode = $stmt->fetch();

        if ($dbNode['id'] !== 'user_created' || $dbNode['label'] !== 'User Created' || $dbNode['category'] !== 'application' || $dbNode['type'] !== 'database' || $dbNode['user_created'] !== 1) {
            print_r($dbNode);
            throw new Exception('error on testInsertNode');
        }

        try {
            $this->database->insertNode(new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to insert node. Node already exists: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: nodes.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }
        throw new Exception('error on testInsertNode');
    }

    public function testBatchInsertNodes(): void {
        $nodes = [
            new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']),
            new NodeDTO('node2', 'Node 02', 'application', 'database', false, ['running_on' => 'SRV011P']),
            new NodeDTO('node3', 'Node 03', 'application', 'service', false, ['running_on' => 'SRV012P']),
            new NodeDTO('node1', 'Node 01', 'business', 'service', false, ['running_on' => 'SRV01OP']),
        ];

        try {
            $this->database->batchInsertNodes($nodes);
        } catch (Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to batch insert nodes. Node already exists: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: nodes.id") {
                throw new Exception('unique constraint expected');
            }
        }

        $nodes = [
            new NodeDTO('node4', 'Node 04', 'business', 'service', false, ['running_on' => 'SRV01OP']),
            new NodeDTO('node5', 'Node 05', 'application', 'database', false, ['running_on' => 'SRV011P']),
            new NodeDTO('node6', 'Node 06', 'application', 'service', false, ['running_on' => 'SRV012P']),
        ];

        $this->database->batchInsertNodes($nodes);

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        
        foreach ($nodes as $n) {
            $stmt->execute([':id' => $n->id]);
            $node = $stmt->fetch();

            if ($node['id'] !== $n->id || $node['label'] !== $n->label || $node['category'] !== $n->category || $node['type'] !== $n->type) {
                throw new Exception('error on batchInsertNodes');
            }
            if (json_decode($node['data'], true)['running_on'] !== $n->data['running_on']) {
                throw new Exception('error on batchInsertNodes');
            }
        }
    }

    public function testUpdateNode(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV011P"}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV012P"}\')');
        
        $this->database->updateNode(new NodeDTO('node1', 'Novo Label', 'application', 'database', false, ['other' => 'diff']));

        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node1']);
        $node = $stmt->fetch();

        if ($node['id'] !== 'node1' || $node['label'] !== 'Novo Label' || $node['category'] !== 'application' || $node['type'] !== 'database') {
            throw new Exception('error on testUpdateNode 0');
        }
        if (json_decode($node['data'], true)['other'] !== 'diff') {
            throw new Exception('error on testUpdateNode 1');
        }

        if ($this->database->updateNode(new NodeDTO('node3', 'Novo Label', 'application', 'database', false, ['other' => 'diff']))) {
            throw new Exception('error on testUpdateNode 2');
        }
    }

    public function testDeleteNode(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV011P"}\')');

        // Test deleting the node
        if (!$this->database->deleteNode('node2')) {
            throw new Exception('error on testDeleteNode - delete should succeed');
        }

        // Verify node was deleted
        $stmt = $this->pdo->prepare('select * from nodes where id = :id');
        $stmt->execute([':id' => 'node2']);
        $node = $stmt->fetch();
        if ($node !== false) {
            throw new Exception('error on testDeleteNode - node should be deleted');
        }

        // Test deleting non-existent node
        if ($this->database->deleteNode('node4')) {
            throw new Exception('error on testDeleteNode - should return false for non-existent node');
        }
    }

    public function testGetEdge(): void {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        $edge = $this->database->getEdge('node1', 'node2');
        if ($edge !== null) {
            throw new Exception('error on testGetEdge');
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "application", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "business", "database", \'{}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge1", "node1", "node2", "label", \'{"a":"b"}\')');
        
        $edge = $this->database->getEdge('edge1');
        
        if ($edge->id !== 'edge1' || $edge->source !== 'node1' || $edge->target !== 'node2') {
            throw new Exception('error on testGetEdge');
        }
        if ($edge->data['a'] !== 'b') {
            throw new Exception('error on testGetEdge');
        }
    }

    public function testGetEdges(): void {
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');

        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV02'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV03'])]);

        $stmt = $this->pdo->prepare('select * from nodes');
        $stmt->execute();
        $nodes = $stmt->fetchAll();
        
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge1", "node1", "node2", "label", \'{"a":"b"}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("edge2", "node2", "node3", "label", \'{"b":"c"}\')');
        
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
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'business', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);

        $this->database->insertEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['a' => 'b']));

        $stmt = $this->pdo->prepare('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $dbEdge = $stmt->fetch();

        if ($dbEdge['id'] !== 'edge1' || $dbEdge['source'] !== 'node1' || $dbEdge['target'] !== 'node2') {
            throw new Exception('error on testInsertEdge 1');
        }

        if (json_decode($dbEdge['data'], true)['a'] !== 'b') {
            throw new Exception('error on testInsertEdge 2');
        }

        try {
            $this->database->insertEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['a' => 'b']));
        } catch(Exception $e) {
            if ($e->getMessage() != "Database Error: Failed to insert edge. Edge already exists: edge1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: edges.id") {
                throw new Exception('unique constraint expected');
            }
            return;
        }

        throw new Exception('error on testInsertEdge 3');
    }

    public function testBatchInsertEdges(): void
    {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV012P'])]);
        
        $edges = [
            ['id' => 'edge1', 'source' => 'node1', 'target' => 'node2', 'label' => 'label1', 'data' => ['a' => 'b']],
            ['id' => 'edge2', 'source' => 'node2', 'target' => 'node3', 'label' => 'label2', 'data' => ['b' => 'c']],
        ];

        $this->database->batchInsertEdges($edges);

        $stmt = $this->pdo->prepare('select * from edges where source = :source and target = :target');
        
        foreach ($edges as $e) {
            $stmt->execute([':source' => $e['source'], ':target' => $e['target']]);
            $edge = $stmt->fetch();

            if ($edge['id'] !== $e['id'] || $edge['source'] !== $e['source'] || $edge['target'] !== $e['target']) {
                throw new Exception('error on batchInsertEdges 1');
            }
            foreach ($e['data'] as $key => $value) {
                if (json_decode($edge['data'], true)[$key] !== $value) {
                    throw new Exception('error on batchInsertEdges 2');
                }
            }
        }

        $edge = ['id' => 'edge1', 'source' => 'node1', 'target' => 'node2', 'label' => 'label1', 'data' => ['a' => 'b']];

        try {
            $this->database->batchInsertEdges([$edge]);
        } catch (Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to batch insert edges. Edge already exists: edge1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: edges.id") {
                throw new Exception('unique constraint expected');
                return;
            }
        }
    }

    public function testUpdateEdge(): void
    {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);

        $stmt = $this->pdo->prepare('insert into edges (id, source, target, label, data) values (:id, :source, :target, :label, :data)');
        $stmt->execute([':id' => 'edge1', ':source' => 'node1', ':target' => 'node2', ':label' => 'label', ':data' => json_encode(['a' => 'b'])]);


        $this->database->updateEdge(new EdgeDTO('edge1', 'node1', 'node2', 'label', ['x' => 'y']));

        $stmt = $this->pdo->prepare('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $edge = $stmt->fetch();
        $edge['data'] = json_decode($edge['data'], true);

        if ($edge['id'] !== 'edge1' || $edge['source'] !== 'node1' || $edge['target'] !== 'node2') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($edge['data']['x'] !== 'y') {
            throw new Exception('error on testUpdateEdge');
        }

        if ($this->database->updateEdge(new EdgeDTO('edge3', 'node3', 'node4', 'label', ['x' => 'y']))) {
            throw new Exception('error on testUpdateEdge');
        }
    }

    public function testDeleteEdge(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);
        $stmt->execute([':id' => 'node2', ':label' => 'Node 02', ':category' => 'application', ':type' => 'database', ':data' => json_encode(['running_on' => 'SRV011P'])]);
        $stmt->execute([':id' => 'node3', ':label' => 'Node 03', ':category' => 'application', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV012P'])]);

        $stmt = $this->pdo->prepare('insert into edges (id, source, target, label, data) values (:id, :source, :target, :label, :data)');
        $stmt->execute([':id' => 'edge1', ':source' => 'node1', ':target' => 'node2', ':label' => 'label', ':data' => json_encode(['a' => 'b'])]);
        $stmt->execute([':id' => 'edge2', ':source' => 'node2', ':target' => 'node3', ':label' => 'label', ':data' => json_encode(['b' => 'c'])]);

        $this->database->deleteEdge('edge1');
        $this->database->deleteEdge('edge2');

        $stmt = $this->pdo->prepare('select * from edges where id = :id');
        $stmt->execute([':id' => 'edge1']);
        $edge = $stmt->fetch();
        if ($edge !== false) {
            throw new Exception('error on testDeleteEdge');
        }

        $stmt->execute([':id' => 'edge2']);
        $edge = $stmt->fetch();
        if ($edge !== false) {
            throw new Exception('error on testDeleteEdge');
        }

        if ($this->database->deleteEdge('edge6')) {
            throw new Exception('error on testDeleteEdge');
        }
    }

    public function testGetNodeStatus(): void {
        $stmt = $this->pdo->prepare('insert into nodes (id, label, category, type, data) values (:id, :label, :category, :type, :data)');
        $stmt->execute([':id' => 'node1', ':label' => 'Node 01', ':category' => 'business', ':type' => 'service', ':data' => json_encode(['running_on' => 'SRV01OP'])]);

        // Test node with no status
        $s = $this->database->getNodeStatus('node1');

        if ($s->node_id !== 'node1' || $s->status !== null) {
            throw new Exception('error on testGetStatus');
        }

        if (!is_null($this->database->getNodeStatus('node2'))) {
            throw new Exception('error on testGetStatus');
        }
    }

    public function testUpdateNodeStatus(): void {
        
        try {
            $this->database->updateNodeStatus(new StatusDTO('node1', 'healthy'));
        } catch(Exception $e) {
            if ($e->getMessage() !== "Database Error: Failed to update node status: node not found for status update: node1. Exception: SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed") {
                throw new Exception('error on testUpdateNodeStatus - node should not exist');
            }
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV01OP"}\')');
        $this->database->updateNodeStatus(new StatusDTO('node1', 'healthy'));

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node1']);
        $s = $stmt->fetch();

        if ($s['node_id'] !== 'node1' || $s['status'] !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus');
        }

        try {
            $this->database->updateNodeStatus(new StatusDTO('node2', 'unhealthy'));
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testUpdateNodeStatus');
    }

    public function testBatchUpdateNodeStatus(): void {
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node1", "Node 01", "business", "service", \'{"running_on":"SRV01OP"}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("node2", "Node 02", "application", "database", \'{"running_on":"SRV011P"}\')');

        $statuses = [
            new StatusDTO('node1', 'healthy'),
            new StatusDTO('node2', 'unhealthy'),
        ];

        $this->database->batchUpdateNodeStatus($statuses);

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node1']);
        $s1 = $stmt->fetch();
        if ($s1['node_id'] !== 'node1' || $s1['status'] !== 'healthy') {
            throw new Exception('error on testBatchUpdateNodeStatus');
        }

        $stmt = $this->pdo->prepare('select * from status where node_id = :node_id');
        $stmt->execute([':node_id' => 'node2']);
        $s2 = $stmt->fetch();
        if ($s2['node_id'] !== 'node2' || $s2['status'] !== 'unhealthy') {
            throw new Exception('error on testBatchUpdateNodeStatus');
        }
    }

    public function testGetProject(): void
    {
        $project = $this->database->getProject('initial');
        if ($project !== null) {
            throw new Exception('error on testGetProject');
        }

        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("a", "Node A", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("b", "Node B", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("c", "Node C", "business", "service", \'{}\')');
        $this->pdo->exec('insert into nodes (id, label, category, type, data) values ("d", "Node D", "business", "service", \'{}\')');
        
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("a-b", "a", "b", "connects", \'{}\')');
        $this->pdo->exec('insert into edges (id, source, target, label, data) values ("c-d", "c", "d", "connects", \'{}\')');

        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');
        
        $this->pdo->exec('insert into nodes_projects (node_id, project_id) values ("a", "initial")');
        $this->pdo->exec('insert into nodes_projects (node_id, project_id) values ("c", "initial")');
        $project = $this->database->getProject('initial');
        
        if ($project->id !== 'initial' || $project->name !== 'Initial Project' || $project->author !== 'admin') {
            throw new Exception('error on validation of project');
        }

        if(count($project->graph->nodes) !== 4) {
            throw new Exception("unexpected number of nodes");
        }

        if (count($project->graph->edges) !== 2) {
            throw new Exception("Unexpected number of edges");
        }

        if ($project->graph->nodes[0]->id !== 'a' || $project->graph->nodes[1]->id !== 'b') {
            throw new Exception("Unexpected node");
        }

        if ($project->graph->edges[0]->id !== 'a-b' || $project->graph->edges[1]->id !== 'c-d') {
            throw new Exception("Unexpected edge");
        }
    }

    public function testGetProjects(): void
    {
        $this->pdo->exec('delete from projects;');
        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');

        $projects = $this->database->getProjects();
        
        if ($projects[0]->id !== 'initial' || $projects[0]->name !== 'Initial Project' || $projects[0]->author !== 'admin') {
            throw new Exception('error on testGetProjects 3');
        }
    }

    public function testInsertProject(): void
    {
        $this->database->insertProject(
            new ProjectDTO(
                'initial',
                'Initial Project',
                'admin',
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                null,
                ['nodes' => ['a', 'b']])
            );

        $stmt = $this->pdo->prepare('select * from projects where id = :id');
        $stmt->execute([':id' => 'initial']);
        $project = $stmt->fetch();
        
        try {
            $this->database->insertProject(
                new ProjectDTO(
                    'initial',
                    'Initial Project',
                    'admin',
                    new DateTimeImmutable(),
                    new DateTimeImmutable(),
                    null,
                    ['nodes' => ['a', 'b']]
                )
            );
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testInsertProject');
    }

    public function testUpdateProject(): void
    {
        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');

        $this->database->updateProject(
            new ProjectDTO(
                'initial',
                'Updated Project',
                'admin',
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                null,
                ['nodes' => []]
            )
        );
        
        $stmt = $this->pdo->prepare('select * from projects where id = :id');
        $stmt->execute([':id' => 'initial']);
        $projects = $stmt->fetchAll();

        if (count($projects) !== 1) {
            throw new Exception('error on testUpdateProject 1');
        }

        if ($projects[0]['id'] !== 'initial' || $projects[0]['name'] !== 'Updated Project' || $projects[0]['author'] !== 'admin') {
            throw new Exception('error on testUpdateProject 2');
        }

        if ($this->database->updateProject(
            new ProjectDTO(
                'nonexistent', 
                'Name', 
                'admin', 
                new DateTimeImmutable(), 
                new DateTimeImmutable(),
                null, 
                ['nodes' => []]))
        ) {
            throw new Exception('error on testUpdateProject 3');
        }
    }

    public function testDeleteProject(): void
    {
        $this->pdo->exec('delete from projects;');
        $this->pdo->exec('insert into projects (id, name, author, data) values ("initial", "Initial Project", "admin", \'{}\')');

        if (! $this->database->deleteProject('initial')) {
            throw new Exception('error on testDeleteProject 1');
        }

        $projects = $this->database->getProjects();
        if (count($projects) !== 0) {
            throw new Exception('error on testDeleteProject 2');
        }

        if ($this->database->deleteProject('nonexistent')) {
            throw new Exception('error on testDeleteProject 3');
        }
    }

    public function testGetLogs(): void {
        $logs = $this->database->getLogs(2);
        if (count($logs) > 0) {
            throw new Exception('error on testGetLogs');
        }

        $this->database->insertLog(new LogDTO('node', 'node1', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));
        sleep(1);
        $this->database->insertLog(new LogDTO('node', 'node2', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));

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
        $this->database->insertLog(new LogDTO('node', 'node1', 'update', null, null, 'admin', '127.0.0.1', new DateTimeImmutable()));
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
        $newNode = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value']);
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
        
        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'business', 'database', false, ['key' => 'value2']);
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
        $node = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value']);
        $this->service->insertNode($node);
        $retrievedNode = $this->service->getNode('node1');
        if ($retrievedNode->getId() !== 'node1' || $retrievedNode->getLabel() !== 'Node 01') {
            throw new Exception('error on testInsertNode');
        }
        // Test with contributor permission
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
        $this->service->insertNode($node2);
        $retrievedNode2 = $this->service->getNode('node2');
        if ($retrievedNode2->getId() !== 'node2') {
            throw new Exception('error on testInsertNode - contributor should be able to insert');
        }
    }
    
    public function testUpdateNode(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value']);
        $this->service->insertNode($node);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', false, ['key' => 'newvalue']);
        
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
        $updatedNode = new Node('node5', 'Updated Node', 'application', 'database', false, ['key' => 'newvalue']);
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

        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
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

        $node1 = new Node('node1', 'Node 01', 'business', 'service', true, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', true, ['key' => 'value2']);
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
        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'application', 'service', false, ['key' => 'value3']);
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
        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
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

        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
        $node3 = new Node('node3', 'Node 03', 'application', 'service', false, ['key' => 'value3']);
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
        
        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
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
    
    public function testGetNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $this->service->insertNode($node1);
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'unknown') {
            throw new Exception('error on testGetNodeStatus - default should be unknown');
        }
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        $status = $this->service->getNodeStatus('node1');
        if ($status->getNodeId() !== 'node1' || $status->getStatus() !== 'healthy') {
            throw new Exception('error on testGetNodeStatus - status should be healthy');
        }
    }
    
    public function testUpdateNodeStatus(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');

        $node1 = new Node('node1', 'Node 01', 'business', 'service', true, ['key' => 'value1']);
        $this->service->insertNode($node1);
        $this->service->updateNodeStatus(new Status('node1', 'healthy'));
        
        $dbStatus = $this->graphDB->getNodeStatus('node1');
        if ($dbStatus === null) {
            throw new Exception('error on testUpdateNodeStatus - node not found');
        }

        if ($dbStatus->status !== 'healthy') {
            throw new Exception('error on testUpdateNodeStatus - status not set');
        }
        $this->service->updateNodeStatus(new Status('node1', 'maintenance'));
        
        $dbStatus = $this->graphDB->getNodeStatus('node1');
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
                null));
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
                null
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
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null));
        $projects = $this->service->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on testInsertProject - should have 1 project');
        }

        if($projects[0]->getId() !== 'project1' || $projects[0]->getName() !== 'First Project') {
            throw new Exception('error on testInsertProject - project data mismatch');
        }

        try {
            $this->service->insertProject(new Project('project1', 'Duplicate Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null));
        } catch (Exception $e) {
            return;
        }
        throw new Exception('error on testInsertProject - should not allow duplicate project IDs');
    }

    public function testUpdateProject(): void
    {
        $this->pdo->exec('delete from projects');

        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null));
        $this->service->updateProject(new Project('project1', 'Updated Project Name', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null));
        $projects = $this->service->getProjects();
        if (count($projects) !== 1) {
            throw new Exception('error on testUpdateProject - should have 1 project');
        }

        if($projects[0]->getName() !== 'Updated Project Name') {
            throw new Exception('error on testUpdateProject - project name not updated');
        }
        
        if($this->service->updateProject(new Project('project2', 'Non-existent Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null))) {
            throw new Exception('error on testUpdateProject - should return false for non-existent project');
        }
    }

    public function testDeleteProject(): void
    {
        $this->pdo->exec('delete from projects');

        HelperContext::update('admin', 'admin', '127.0.0.1');
        
        $this->service->insertProject(new Project('project1', 'First Project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), null));
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

        $node1 = new Node('node1', 'Node 01', 'business', 'service', false, ['key' => 'value1']);
        $this->service->insertNode($node1);
        sleep(1);
        $updatedNode = new Node('node1', 'Updated Node', 'application', 'database', false, ['key' => 'newvalue']);
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

#####################################

