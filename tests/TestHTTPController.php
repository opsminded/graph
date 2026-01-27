<?php

declare(strict_types=1);

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
        global $DATA_IMAGES;

        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->imagesHelper = new HelperImages($DATA_IMAGES);
        
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

    public function testGetCategories(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getCategories';
        $req = new HTTPRequest();

        $resp = $this->controller->getCategories($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::getCategories\'') {
            print_r($resp);
            throw new Exception('error on testGetCategories 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $req = new HTTPRequest();
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
        $req = new HTTPRequest();

        $resp = $this->controller->getTypes($req);

        if ($resp->code != 405 || $resp->message != 'method \'DELETE\' not allowed in \'HTTPController::getTypes\'') {
            throw new Exception('error on testGetTypes');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getTypes';
        $req = new HTTPRequest();
        $resp = $this->controller->getTypes($req);
        if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) === 0) {
            throw new Exception('error on testGetTypes');
        }
        
        if ($resp->data[0]['id'] !== 'business' || $resp->data[1]['id'] !== 'business_case') {
            throw new Exception('error on testGetTypes');
        }
    }

    public function testgetCytoscapeGraph(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getGraph';
        $req = new HTTPRequest();
        $resp = $this->controller->getCytoscapeGraph($req);
        if ($resp->code != 405 || $resp->message != 'method \'PUT\' not allowed in \'HTTPController::getCytoscapeGraph\'') {
            throw new Exception('error on testGetGraph');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getGraph';

        $req = new HTTPRequest();
        $resp = $this->controller->getCytoscapeGraph($req);
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
        $req->data['user_created'] = true;
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

        $this->database->insertNode('node1', 'label1', 'application', 'service');
        $this->database->insertNode('node2', 'label2', 'application', 'service');
        $this->database->insertEdge('node1-node2', 'node1', 'node2', 'label');

        $req = new HTTPRequest();
        $resp = $this->controller->getEdges($req);

        if ($resp->code !== 200 || count($resp->data) !== 1) {
            throw new Exception('error on testGetEdges');
        }
        if ($resp->data[0]['source'] !== 'node1' || $resp->data[0]['target'] !== 'node2') {
            throw new Exception('error on testGetEdges');
        }
        
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

    public function testGetProject(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $req = new HTTPRequest();
        $resp = $this->controller->getProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getProject\'') {
            throw new Exception('error on testGetProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $req = new HTTPRequest();
        $resp = $this->controller->getProject($req);
        if ($resp->code !== 400 || $resp->message !== 'param \'id\' is missing') {
            throw new Exception('error on testGetProject 2');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProject';
        $_GET['id'] = 'meu-project';
        $req = new HTTPRequest();
        $resp = $this->controller->getProject($req);
        if( $resp->code !== 404 || $resp->message !== 'project not found') {
            print_r($resp);
            throw new Exception('error on testGetProject 3');
        }
        
        $this->database->insertProject('meu-project', 'meu project', 'admin', ['nodes' => ['a', 'b']]);
        
        $req = new HTTPRequest();
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
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProjects';
        $req = new HTTPRequest();
        $resp = $this->controller->getProjects($req);
        if ($resp->code != 405 || $resp->message != 'method \'POST\' not allowed in \'HTTPController::getProjects\'') {
            throw new Exception('error on testGetProjects 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getProjects';
        $req = new HTTPRequest();
        $resp = $this->controller->getProjects($req);
        if( $resp->code !== 200 || $resp->message !== 'projects found') {
            throw new Exception('error on testGetProjects 2');
        }
        
        $this->database->insertProject('meu-project', 'meu project', 'admin', ['nodes' => ['a', 'b']]);
        
        $req = new HTTPRequest();
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertProject';
        $req = new HTTPRequest();
        $resp = $this->controller->insertProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::insertProject\'') {
            throw new Exception('error on testInsertProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertProject';
        $req = new HTTPRequest();
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
        $req = new HTTPRequest();
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
        $req = new HTTPRequest();
        $resp = $this->controller->updateProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::updateProject\'') {
            throw new Exception('error on testUpdateProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateProject';
        $req = new HTTPRequest();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1 Updated';
        $req->data['creator'] = 'admin';
        $req->data['nodes'] = [];
        $resp = $this->controller->updateProject($req);
        if ($resp->code !== 404 || $resp->message !== 'project not updated' || $resp->data['id'] !== 'project1') {
            print_r($resp);
            throw new Exception('error on testUpdateProject 2');
        }

        $this->database->insertProject('project1', 'My Project 1', 'admin', ['nodes' => [], 'edges' => []]);
        $req = new HTTPRequest();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1 Updated';
        $req->data['creator'] = 'admin';
        $req->data['nodes'] = ['node1', 'node2'];
        $resp = $this->controller->updateProject($req);
        if ($resp->code !== 200 || $resp->message !== 'project updated' || $resp->data['name'] !== 'My Project 1 Updated' || count($resp->data['nodes']) !== 2) {
            print_r($resp);
            throw new Exception('error on testUpdateProject 3');
        }
    }

    public function testDeleteProject(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteProject';
        $req = new HTTPRequest();
        $resp = $this->controller->deleteProject($req);
        if ($resp->code != 405 || $resp->message != 'method \'GET\' not allowed in \'HTTPController::deleteProject\'') {
            throw new Exception('error on testDeleteProject 1');
        }

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/deleteProject';
        $req = new HTTPRequest();
        $req->data['id'] = 'project1';
        $resp = $this->controller->deleteProject($req);
        if ($resp->code !== 404 || $resp->message !== 'project not deleted' || $resp->data['id'] !== 'project1') {
            print_r($resp);
            throw new Exception('error on testDeleteProject 2');
        }

        $this->database->insertProject('project1', 'My Project 1', 'admin', ['nodes' => [], 'edges' => []]);
        $req = new HTTPRequest();
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
