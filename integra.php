<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Opsminded\Graph\Logger;
use Opsminded\Graph\Repository\AuditRepoImpl;
use Opsminded\Graph\Repository\LoggerRepoImpl;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;
use Opsminded\Graph\Repository\SqliteStatusRepoImpl;

$loggerFilename = __DIR__ . '/gdmon.log';

$dbFilename = __DIR__ . '/gdmon.db';
if (file_exists($dbFilename)) {
    @unlink($dbFilename);
}

$logger = new Logger($loggerFilename);
$pdo = SqliteGraphRepoImpl::createConnection($dbFilename);

$status = new SqliteStatusRepoImpl($pdo);

$repo = new SqliteGraphRepoImpl($pdo);
$repo = new LoggerRepoImpl($repo, $logger);
$repo = new AuditRepoImpl($pdo, $repo);
$repo->insertNode('xpto', ['category' => 'business', 'type' => 'server']);

$status->setNodeStatus('xpto', 'healthy');

################################################################
// public function getNode(string $id): ?array;
$node = $repo->getNode('xpto');
print('Node xpto:' . PHP_EOL);
print(json_encode($node) . PHP_EOL);

// public function getNodes(): array;
$nodes = $repo->getNodes();
print('All nodes:' . PHP_EOL);
print(json_encode($nodes) . PHP_EOL);


// public function getNodeExists(string $id): bool;
$exists = $repo->getNodeExists('xpto');
print('Node xpto exists: ' . ($exists ? 'true' : 'false') . PHP_EOL);

// public function insertNode(string $id, array $data): bool;
$repo->insertNode('node2', ['category' => 'application', 'type' => 'web']);
print('Inserted node2' . PHP_EOL);

// public function updateNode(string $id, array $data): bool;
$repo->updateNode('node2', ['category' => 'application', 'type' => 'database']);
$node = $repo->getNode('node2');
print('Updated node2:' . PHP_EOL);
print(json_encode($node) . PHP_EOL);

// public function deleteNode(string $id): bool;
$repo->deleteNode('node2');
print('Deleted node2' . PHP_EOL);

$repo->insertNode('Node 3', ['category' => 'application', 'type' => 'server']);

// public function getEdge(string $source, string $target): ?array;
$repo->insertEdge('xpto', 'Node 3', ['relation' => 'connects_to']);

// public function getEdges(): array;
$edges = $repo->getEdges();
print('All edges:' . PHP_EOL);
print(json_encode($edges) . PHP_EOL);

// public function getEdgeExists(string $source, string $target): bool;
$exists = $repo->getEdgeExists('xpto', 'Node 3');
print('Edge xpto -> Node 3 exists: ' . ($exists ? 'true' : 'false') . PHP_EOL);

// public function insertEdge(string $source, string $target, array $data = []): bool;
$repo->insertEdge('xpto', 'Node 3', ['relation' => 'connects_to']);
print('Inserted edge xpto -> Node 3' . PHP_EOL);

// public function updateEdge(string $source, string $target, array $data = []): bool;
$repo->updateEdge('xpto', 'Node 3', ['relation' => 'depends_on']);
$edge = $repo->getEdge('xpto', 'Node 3');
print('Updated edge xpto -> Node 3:' . PHP_EOL);
print(json_encode($edge) . PHP_EOL);

// public function deleteEdge(string $source, string $target): bool;
$repo->deleteEdge('xpto', 'Node 3');
print('Deleted edge xpto -> Node 3' . PHP_EOL);

$repo->insertEdge('xpto', 'Node 3', ['relation' => 'connects_to']);

echo "Database contents:\n";
################################################################
$stmt = $pdo->query('SELECT * FROM nodes');
$rows = $stmt->fetchAll();
print('Nodes:' . PHP_EOL);
print(json_encode($rows) . PHP_EOL);

$stmt = $pdo->query('select * from edges');
$rows = $stmt->fetchAll();
print('Edges:' . PHP_EOL);
print(json_encode($rows) . PHP_EOL);

$stmt = $pdo->query('SELECT * FROM audit');
$rows = $stmt->fetchAll();
print('Audit:' . PHP_EOL);
print(json_encode($rows) . PHP_EOL);

$stmt = $pdo->query('SELECT * FROM status');
$rows = $stmt->fetchAll();
print('Status:' . PHP_EOL);
print(json_encode($rows) . PHP_EOL);

$pdo = null;
$repo = null;
$logger = null;
echo "OK\n";