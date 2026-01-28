<?php

declare(strict_types=1);

class TestHelperCytoscape extends TestAbstractTest
{
    private ?PDO $pdo;
    private ?LoggerInterface $logger;
    private ?Database $database;

    public function up(): void
    {
        global $SQL_SCHEMA;
        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->logger = new Logger();
        $this->database = new Database($this->pdo, $this->logger, $SQL_SCHEMA);

        $this->pdo->exec('delete from audit');
        $this->pdo->exec('delete from nodes');
        $this->pdo->exec('delete from edges');
        $this->pdo->exec('delete from projects');
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
