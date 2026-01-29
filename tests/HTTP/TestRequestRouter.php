<?php

declare(strict_types=1);

class TestRequestRouter extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?DatabaseInterface $database;
    private ?ServiceInterface $service;
    private ?ControllerInterface $controller;
    private ?HelperCytoscape $cytoscapeHelper;

    private ?RequestRouter $router;

    public function up(): void
    {
        global $SQL_SCHEMA;

        $_GET = [];
        $_SERVER = [];

        $this->pdo = Database::createConnection('sqlite::memory:');

        $this->databaseLogger = new Logger();
        $this->serviceLogger = new Logger();
        $this->controllerLogger = new Logger();

        $this->database = new Database($this->pdo, $this->databaseLogger, $SQL_SCHEMA);

        $this->cytoscapeHelper = new HelperCytoscape($this->database, 'http://localhost/images');

        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new Controller($this->service, $this->cytoscapeHelper, $this->controllerLogger);
        $this->router = new RequestRouter($this->controller);

        $this->pdo->exec('delete from audit');
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        $this->pdo->exec('delete from projects');
    }

    public function down(): void
    {
        $this->router = null;
        $this->controller = null;
        $this->cytoscapeHelper = null;
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
        $req->data['id'] = 'joao';
        $req->data['group'] = 'contributor';
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
        $req->data['id'] = 'joao';
        $req->data['group'] = 'contributor';

        $resp = $this->router->handle($req);
        if($resp->code !== 500 || $resp->message !== 'method not found in list') {
            print_r($resp);
            exit();
            throw new Exception('error on testRequestRouterException');
        }
    }
}