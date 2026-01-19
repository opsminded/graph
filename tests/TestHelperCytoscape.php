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
        include __DIR__ . "/compiled_images.php";

        $img = new HelperImages($images);
        $cy = new HelperCytoscape($this->database, $img, 'http://example.com/images');

        $nodes = [
            new ModelNode('n1', 'Node 1', 'business', 'server',  false, ['a' => 1]),
            new ModelNode('n2', 'Node 2', 'business', 'server', false, ['b' => 2]),
            new ModelNode('n3', 'Node 3', 'business', 'server', false, ['c' => 3]),
        ];

        $edges = [
            new ModelEdge('n1', 'n2', 'label1'),
            new ModelEdge('n2', 'n3', 'label2'),
        ];

        $graph = new ModelGraph($nodes, $edges);
        $data = $cy->toArray($graph);
        // print_r($data);
        // exit();
    }
}
