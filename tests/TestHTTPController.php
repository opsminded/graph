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

    public function up(): void
    {
        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger('database.log');
        $this->serviceLogger = new Logger('service.log');
        $this->controllerLogger = new Logger('controller.log');

        $this->database = new Database($this->pdo, $this->databaseLogger);
        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new HTTPController($this->service, $this->controllerLogger);
    }

    public function down(): void
    {
        $this->controller = null;
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
        $_GET['id'] = 'maria';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getUser';

        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if ($resp->code != 404 || $resp->status != 'error' || $resp->message != 'user not found') {
            throw new Exception('error on testGetUser');
        }

        $this->database->insertUser('maria', 'contributor');

        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if($resp->code != 200 || $resp->status != 'success' || $resp->message != 'user found' || $resp->data['id'] != 'maria') {
            throw new Exception('error on testGetUser');
        }

        unset($_GET['id']);
        $req = new HTTPRequest();
        $resp = $this->controller->getUser($req);
        if($resp->code != 400 || $resp->status != 'error' || $resp->message != 'param \'id\' not found') {
            throw new Exception('error on testGetUser');
        }
    }

    public function testInsertUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        
        $req = new HTTPRequest();
        $req->data['id'] = 'maria';
        $req->data['user_group'] = 'admin';

        $resp = $this->controller->insertUser($req);
        if($resp->code != 201 || $resp->status != 'success' || $resp->message != 'user created' || $resp->data['id'] != 'maria' || $resp->data['group']['id'] != 'admin') {
            throw new Exception('error on testInsertUser');
        }

        $req->data = [];
        $resp = $this->controller->insertUser($req);
        if($resp->code != 400 || $resp->message != 'key id not found in data') {
            throw new Exception('error on testInsertUser');
        }
        
        $req->data['id'] = 'maria';
        $resp = $this->controller->insertUser($req);
        if($resp->code != 400 || $resp->message != 'key user_group not found in data') {
            throw new Exception('error on testInsertUser');
        }
    }

    public function testUpdateUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateUser';

        $req = new HTTPRequest();
        $req->data['id'] = 'maria';
        $req->data['user_group'] = 'admin';

        $resp = $this->controller->updateUser($req);
        if($resp->code != 404 || $resp->status != 'error' || $resp->data['id'] != 'maria') {
            throw new Exception('error on testUpdateUser');
        }

        $this->database->insertUser('maria', 'contributor');
        $resp = $this->controller->updateUser($req);
        if($resp->code != 200 || $resp->status != 'success' || $resp->message != 'user updated' || $resp->data['id'] != 'maria') {
            throw new Exception('error on testUpdateUser');
        }

    }

    public function testGetGraph(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getGraph';

        $req = new HTTPRequest();
        $resp = $this->controller->getGraph($req);
    }

    public function testGetNode(): void
    {
        $_GET['id'] = 'node1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNode';

        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if($resp->code != 404 || $resp->message != 'node not found') {
            throw new Exception('error on testGetNode');
        }
        
        $this->database->insertNode('node1', 'label 1', 'application', 'server');
        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if($resp->code != 200 || $resp->status != 'success' || $resp->message != 'node found') {
            throw new Exception('error on testGetNode');
        }

        $_GET = [];
        $req = new HTTPRequest();
        $resp = $this->controller->getNode($req);
        if($resp->code != 400 || $resp->message != 'param \'id\' not found') {
            throw new Exception('error on testGetNode');
        }
    }

    public function testGetNodes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';

        $req = new HTTPRequest();
        $resp = $this->controller->getNodes($req);
        if($resp->code != 200 || count($resp->data) > 0) {
            throw new Exception('error on testGetNodes');
        }

        $this->database->insertNode('node1', 'label1', 'application', 'server');
        $this->database->insertNode('node2', 'label2', 'application', 'server');
        $req = new HTTPRequest();
        $resp = $this->controller->getNodes($req);
        if($resp->code != 200 || count($resp->data) != 2) {
            throw new Exception('error on testGetNodes');
        }
        if($resp->data[0]['id'] != 'node1' || $resp->data[1]['id'] != 'node2') {
            throw new Exception('error on testGetNodes');
        }
    }

    public function testInsertNode(): void
    {
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
    }

    public function testGetEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdge';

        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->getEdge($req);
    }
    
    public function testGetEdges(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getEdges';

        $req = new HTTPRequest();
        $resp = $this->controller->getEdges($req);
    }
    
    public function testInsertEdge(): void
    {
        $this->database->insertNode('node1', 'label1', 'application', 'server');
        $this->database->insertNode('node2', 'label2', 'application', 'server');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertEdge';

        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $resp = $this->controller->insertEdge($req);
    }
    
    public function testUpdateEdge(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/updateEdge';
        $req = new HTTPRequest();
        $req->data['source'] = 'node1';
        $req->data['target'] = 'node2';
        $req->data['data'] = ['a' => 'b'];
        $resp = $this->controller->updateEdge($req);
    }
    
    public function testDeleteEdge(): void
    {
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getStatus($req);
    }
    
    public function testGetNodeStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodeStatus';
        $req = new HTTPRequest();
        $resp = $this->controller->getNodeStatus($req);
    }
    
    public function testUpdateNodeStatus(): void
    {
        $this->database->insertNode('node1', 'label', 'application', 'server');
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
        $_GET['limit'] = 2;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getLogs';
        $req = new HTTPRequest();
        $resp = $this->controller->getLogs($req);
    }
}
