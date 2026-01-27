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
        $_SERVER['REQUEST_URI'] = 'api.php/updateUser';
        $req = new Request();
        $req->data[User::USER_KEYNAME_ID] = 'joao';
        $req->data[User::USER_KEYNAME_GROUP] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 500 || $resp->message !== 'method not found in list') {
            throw new Exception('error on testRequestRouterException');
        }
    }
}