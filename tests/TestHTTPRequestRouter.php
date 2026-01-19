<?php

declare(strict_types=1);

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
        include __DIR__ . "/compiled_images.php";

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