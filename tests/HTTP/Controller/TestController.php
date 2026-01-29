<?php

declare(strict_types=1);

final class TestController extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?ControllerInterface $controller;

    private ?HelperCytoscape $cytoscapeHelper;
    

    public function up(): void
    {
        global $DATA_IMAGES;
        global $SQL_SCHEMA;

        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->database = new Database($this->pdo, $this->databaseLogger, $SQL_SCHEMA);

        $this->cytoscapeHelper = new HelperCytoscape($this->database, 'http://example.com/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new Controller($this->service, $this->cytoscapeHelper, $this->controllerLogger);

        $this->pdo->exec('delete from audit');
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        $this->pdo->exec('delete from projects');
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
        
        $this->database->insertNode(new NodeDTO('node1', 'label 1', 'application', 'service', []));
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

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', []));
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
        $this->database->insertNode(new NodeDTO('node1', 'label 1', 'application', 'service', []));
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

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', []));
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

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', []));
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

        $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', []));
        $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', []));
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
    //     $this->database->insertNode(new NodeDTO('node1', 'label1', 'application', 'service', []));
    //     $this->database->insertNode(new NodeDTO('node2', 'label2', 'application', 'service', []));

    //     $req = new Request();
    //     $resp = $this->controller->getStatus($req);
    //     if ($resp->code !== 200 || $resp->status !== 'success' || count($resp->data) !== 2) {
    //         throw new Exception('error on testGetStatus');
    //     }
    //     if ($resp->data[0]['node_id'] !== 'node1' || $resp->data[1]['node_id'] !== 'node2') {
    //         throw new Exception('error on testGetStatus');
    //     }
    // }

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

        $this->database->insertNode(new NodeDTO('node1', 'label', 'application', 'service', []));
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
        
        $this->database->insertProject(new ProjectDTO('meu-project', 'meu project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['nodes' => ['a', 'b']]));
        
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

        $this->database->insertProject(new ProjectDTO('meu-project', 'meu project', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['nodes' => ['a', 'b']]));
        
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
        $req->data['data'] = ['a', 'b'];
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
        $req->data['data'] = [];
        $resp = $this->controller->updateProject($req);
        if ($resp->code !== 404 || $resp->message !== 'project not updated' || $resp->data['id'] !== 'project1') {
            print_r($resp);
            throw new Exception('error on testUpdateProject 2');
        }

        $this->database->insertProject(new ProjectDTO('project1', 'My Project 1', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['nodes' => [], 'edges' => []]));
        $req = new Request();
        $req->data['id'] = 'project1';
        $req->data['name'] = 'My Project 1 Updated';
        $req->data['creator'] = 'admin';
        $req->data['data'] = ['node1', 'node2'];
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

        $this->database->insertProject(new ProjectDTO('project1', 'My Project 1', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['nodes' => [], 'edges' => []]));
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
