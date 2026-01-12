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
        try {
            $req = new HTTPRequest();
            $resp = $this->controller->getUser($req);
        } catch(Exception $e) {
            return;
        }
        throw new Exception('error on testGetUser');
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
        if($resp->code != 201 || $resp->status != 'success' || $resp->message != 'user created' || $resp->data['id'] != 'maria' || $resp->data['user_group'] != 'admin') {
            throw new Exception('error on testInsertUser');
        }
    }

    public function testUpdateUser(): void
    {
    }

    public function testGetGraph(): void
    {
    }

    public function testGetNode(): void
    {
    }

    public function testGetNodes(): void
    {
    }

    public function testGnsertNode(): void
    {
    }
    
    public function testGpdateNode(): void
    {
    }
    
    public function testGeleteNode(): void
    {
    }

    public function testGetEdge(): void
    {
    }
    
    public function testGetEdges(): void
    {
    }
    
    public function testInsertEdge(): void
    {
    }
    
    public function testUpdateEdge(): void
    {
    }
    
    public function testDeleteEdge(): void
    {
    }

    public function testGetStatuses(): void
    {
    }
    
    public function testGetNodeStatus(): void
    {
    }
    
    public function testUpdateNodeStatus(): void
    {
    }

    public function testGetLogs(): void
    {
    }
}
