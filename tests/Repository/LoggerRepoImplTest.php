<?php

declare(strict_types=1);

use Opsminded\Graph\Logger;
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Repository\GraphRepoInterface;
use Opsminded\Graph\Repository\SqliteGraphRepoImpl;
use Opsminded\Graph\Repository\LoggerRepoImpl;

class LoggerRepoImplTest extends TestCase
{
    private string $dbFilename;
    private ?PDO $pdo;

    private string $loggerFilename;
    private ?GraphRepoInterface $repo;

    protected function setUp(): void
    {
        $this->dbFilename = sys_get_temp_dir() . '/graphdb' . uniqid() . '.sqlite';
        $this->pdo = SqliteGraphRepoImpl::createConnection($this->dbFilename);
        $this->repo = new SqliteGraphRepoImpl($this->pdo);
        $this->loggerFilename = sys_get_temp_dir() . '/logger' . uniqid() . '.log';
        $logger = new Logger($this->loggerFilename);
        $this->repo = new LoggerRepoImpl($this->repo, $logger);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->repo = null;

        if (file_exists($this->dbFilename)) {
            @unlink($this->dbFilename);
        }
        if (file_exists($this->loggerFilename)) {
            @unlink($this->loggerFilename);
        }
    }

    private function getLogContent(): string
    {
        return file_get_contents($this->loggerFilename);
    }

    public function testGetNodeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);

        $result = $this->repo->getNode('node1');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('business', $result['data']['category']);
        $this->assertStringContainsString('Getting node with ID: node1', $this->getLogContent());
    }

    public function testGetNodeReturnsNullForNonExistentNode(): void
    {
        $this->assertNull($this->repo->getNode('nonexistent'), 'Node should not exist.');
    }

    public function testGetNodesLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'infrastructure', 'type' => 'server']);

        $result = $this->repo->getNodes();

        $this->assertCount(2, $result);
        $this->assertStringContainsString('Getting all nodes', $this->getLogContent());
    }

    public function testGetNodeExistsLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);

        $exists = $this->repo->getNodeExists('node1');
        $notExists = $this->repo->getNodeExists('nonexistent');

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
        $this->assertStringContainsString('Checking if node with ID: node1 exists', $this->getLogContent());
        $this->assertStringContainsString('Checking if node with ID: nonexistent exists', $this->getLogContent());
    }

    public function testInsertNodeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'server';
        $result = $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application', 'name' => 'Test']);

        $this->assertTrue($result);
        $this->assertStringContainsString('Inserting node with ID: node1', $this->getLogContent());

        $node = $this->repo->getNode('node1');
        $this->assertNotNull($node);
        $this->assertEquals('Test', $node['data']['name']);
    }

    public function testUpdateNodeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'server';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application', 'name' => 'Original']);

        $result = $this->repo->updateNode('node1', 'node1', $category, $type, ['name' => 'Updated']);

        $this->assertTrue($result);
        $this->assertStringContainsString('Updating node with ID: node1', $this->getLogContent());

        $node = $this->repo->getNode('node1');
        $this->assertEquals('Updated', $node['data']['name']);
    }

    public function testDeleteNodeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);

        $result = $this->repo->deleteNode('node1');

        $this->assertTrue($result);
        $this->assertStringContainsString('Deleting node with ID: node1', $this->getLogContent());

        $this->assertFalse($this->repo->getNodeExists('node1'));
    }

    public function testGetEdgeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);

        $result = $this->repo->getEdge('edge1');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertStringContainsString('Getting edge with ID: edge1', $this->getLogContent());
    }

    public function testGetEdgeReturnsNullForNonExistentEdge(): void
    {
        $this->assertNull($this->repo->getEdge('source1'), 'Edge should not exist.');
    }

    public function testGetEdgesLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node3', 'node3', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);
        $this->repo->insertEdge('edge2', 'node2', 'node3', []);

        $result = $this->repo->getEdges();

        $this->assertCount(2, $result);
        $this->assertStringContainsString('Getting all edges', $this->getLogContent());
    }

    public function testGetEdgeExistsLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);

        $exists = $this->repo->getEdgeExistsById('edge1');
        $notExists = $this->repo->getEdgeExistsById('nonexistent');

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
        $this->assertStringContainsString('Checking if edge exists with ID: edge1', $this->getLogContent());
        $this->assertStringContainsString('Checking if edge exists with ID: nonexistent', $this->getLogContent());
    }

    public function testInsertEdgeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);

        $result = $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 10]);
        $this->assertTrue($result);
        $this->assertStringContainsString('Inserting edge from source: node1 to target: node2', $this->getLogContent());

        $edge = $this->repo->getEdge('edge1');
        $this->assertNotNull($edge);
    }

    public function testUpdateEdgeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', ['weight' => 10]);

        $result = $this->repo->updateEdge('edge1', 'node1', 'node2', ['weight' => 20]);

        $this->assertTrue($result);
        $this->assertStringContainsString('Updating edge from source: node1 to target: node2', $this->getLogContent());

        $edge = $this->repo->getEdge('edge1');
        $this->assertEquals(20, $edge['data']['weight']);
    }

    public function testDeleteEdgeLogsAndDelegates(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);

        $result = $this->repo->deleteEdge('edge1');

        $this->assertTrue($result);
        $this->assertStringContainsString('Deleting edge with ID: edge1', $this->getLogContent());

        $this->assertFalse($this->repo->getEdgeExistsById('edge1'));
    }

    public function testMultipleOperationsCreateMultipleLogEntries(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);
        $this->repo->insertNode('node2', 'node2', $category, $type, ['category' => 'infrastructure', 'type' => 'server']);
        $this->repo->insertEdge('edge1', 'node1', 'node2', []);
        $this->repo->getNodes();
        $this->repo->getEdges();

        $logContent = $this->getLogContent();
        $lines = explode(PHP_EOL, trim($logContent));

        // Should have at least 5 log entries
        $this->assertGreaterThanOrEqual(5, count($lines));
        $this->assertStringContainsString('Inserting node with ID: node1', $logContent);
        $this->assertStringContainsString('Inserting node with ID: node2', $logContent);
        $this->assertStringContainsString('Inserting edge from source: node1 to target: node2', $logContent);
        $this->assertStringContainsString('Getting all nodes', $logContent);
        $this->assertStringContainsString('Getting all edges', $logContent);
    }

    public function testLoggingIncludesTimestamps(): void
    {
        $category = 'business';
        $type = 'application';
        $this->repo->insertNode('node1', 'node1', $category, $type, ['category' => 'business', 'type' => 'application']);

        $logContent = $this->getLogContent();
        // Check for timestamp pattern: YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $logContent);
    }
}