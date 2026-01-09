<?php

declare(strict_types=1);

final class TestHTTPController extends TestAbstractTest
{
    private ?PDO $pdo;
    
    private ?Logger $databaseLogger;
    private ?Logger $serviceLogger;
    private ?Logger $controllerLogger;

    private ?GraphDatabaseInterface $database;
    private ?GraphServiceInterface $service;
    private ?HTTPControllerInterface $controller;

    public function up(): void
    {
        $this->pdo = GraphDatabase::createConnection('sqlite::memory:');
        $this->databaseLogger = new Logger('database.log');
        $this->serviceLogger = new Logger('service.log');
        $this->controllerLogger = new Logger('controller.log');

        $this->database = new GraphDatabase($this->pdo, $this->databaseLogger);
        $this->service = new GraphService($this->database, $this->serviceLogger);
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
    }

    public function testInsertUser(): void
    {
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
