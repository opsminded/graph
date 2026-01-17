<?php

declare(strict_types=1);

class TestHelperCytoscape extends TestAbstractTest
{
    private ?PDO $pdo;
    private ?LoggerInterface $logger;
    private ?Database $database;

    public function up(): void
    {
        $this->pdo = Database::createConnection('sqlite::memory:');
        $this->logger = new Logger();
        $this->database = new Database($this->pdo, $this->logger);
    }

    public function down(): void
    {
        $this->pdo = null;
        $this->logger = null;
        $this->database = null;
    }

    public function testHelperCytoscape(): void
    {
        include __DIR__ . "/www/images/compiled_images.php";

        $img = new HelperImages($images);
        $cy = new HelperCytoscape($this->database, $img, 'http://example.com/images');

        $nodes = [
            new ModelNode('n1', 'Node 1', 'business', 'server', ['a' => 1]),
            new ModelNode('n2', 'Node 2', 'business', 'server', ['b' => 2]),
            new ModelNode('n3', 'Node 3', 'business', 'server', ['c' => 3]),
        ];

        $edges = [
            new ModelEdge('n1', 'n2'),
            new ModelEdge('n2', 'n3'),
        ];

        $graph = new ModelGraph($nodes, $edges);
        $data = $cy->toArray($graph);
        // print_r($data);
        // exit();
    }
}
