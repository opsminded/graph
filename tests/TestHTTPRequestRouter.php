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
    private ?HTTPRequestRouter $router;

    public function up(): void
    {
        $_GET = [];
        $_SERVER = [];

        $this->pdo = Database::createConnection('sqlite::memory:');

        $this->databaseLogger = new Logger('database.log');
        $this->serviceLogger = new Logger('service.log');
        $this->controllerLogger = new Logger('controller.log');

        $this->database = new Database($this->pdo, $this->databaseLogger);
        $this->service = new Service($this->database, $this->serviceLogger);
        $this->controller = new HTTPController($this->service, $this->controllerLogger);
        $this->router = new HTTPRequestRouter($this->controller);
    }

    public function down(): void
    {
        $this->router = null;
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

    public function testHTTPRequestRouter(): void
    {
        HelperContext::update('admin', 'admin', '127.0.0.1');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/insertUser';
        $req = new HTTPRequest();
        $req->data['id'] = 'joao';
        $req->data['user_group'] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 201 || $resp->message !== 'user created' || $resp->data['id'] !== 'joao' || $resp->data['group']['id'] !== 'contributor') {
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
        $req->data['id'] = 'joao';
        $req->data['user_group'] = 'contributor';
        $resp = $this->router->handle($req);
        if($resp->code !== 500 || $resp->message !== 'method not found in list') {
            throw new Exception('error on testHTTPRequestRouterException');
        }
    }
}