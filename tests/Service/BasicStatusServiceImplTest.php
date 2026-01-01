<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Service\BasicStatusServiceImpl;
use Opsminded\Graph\Service\StatusServiceInterface;
use Opsminded\Graph\Repository\StatusRepoInterface;
use Opsminded\Graph\Repository\SqliteStatusRepoImpl;
use Opsminded\Graph\Model\Status;
use Opsminded\Graph\Model\Statuses;

class BasicStatusServiceImplTest extends TestCase
{
    private ?string $dbPath;
    private ?PDO $pdo;
    private ?StatusRepoInterface $repo;
    private ?StatusServiceInterface $service;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/status_service_test_' . uniqid() . '.db';
        $this->pdo = new PDO('sqlite:' . $this->dbPath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new SqliteStatusRepoImpl($this->pdo);
        $this->service = new BasicStatusServiceImpl($this->repo);
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $this->repo = null;
        $this->service = null;

        if (file_exists($this->dbPath)) {
            @unlink($this->dbPath);
        }
    }

    // ========================================
    // Constructor Tests
    // ========================================

    public function testConstructorAcceptsStatusRepoInterface(): void
    {
        $mockRepo = $this->createMock(StatusRepoInterface::class);
        $service = new BasicStatusServiceImpl($mockRepo);

        $this->assertInstanceOf(BasicStatusServiceImpl::class, $service);
    }

    public function testServiceImplementsStatusServiceInterface(): void
    {
        $this->assertInstanceOf(StatusServiceInterface::class, $this->service);
    }

    // ========================================
    // getStatuses() Tests
    // ========================================

    public function testGetStatusesReturnsStatusesCollection(): void
    {
        $this->repo->setNodeStatus('node1', 'healthy');
        $this->repo->setNodeStatus('node2', 'unhealthy');
        $this->repo->setNodeStatus('node3', 'maintenance');

        $statuses = $this->service->getStatuses();

        $this->assertInstanceOf(Statuses::class, $statuses);

        $statusArray = iterator_to_array($statuses);
        $this->assertCount(3, $statusArray);

        $this->assertInstanceOf(Status::class, $statusArray[0]);
        /** @var Status $status1 */
        $status1 = $statusArray[0];
        $this->assertSame('node1', $status1->getId());
        $this->assertSame('healthy', $status1->getStatus());

        $this->assertInstanceOf(Status::class, $statusArray[1]);
        /** @var Status $status2 */
        $status2 = $statusArray[1];
        $this->assertSame('node2', $status2->getId());
        $this->assertSame('unhealthy', $status2->getStatus());

        $this->assertInstanceOf(Status::class, $statusArray[2]);
        /** @var Status $status3 */
        $status3 = $statusArray[2];
        $this->assertSame('node3', $status3->getId());
        $this->assertSame('maintenance', $status3->getStatus());
    }

    public function testGetStatusesReturnsEmptyCollectionWhenNoStatuses(): void
    {
        $statuses = $this->service->getStatuses();

        $this->assertInstanceOf(Statuses::class, $statuses);
        $this->assertCount(0, iterator_to_array($statuses));
    }

    public function testGetStatusesOrdersByNodeIdAscending(): void
    {
        $this->repo->setNodeStatus('node3', 'healthy');
        $this->repo->setNodeStatus('node1', 'unhealthy');
        $this->repo->setNodeStatus('node2', 'maintenance');

        $statuses = $this->service->getStatuses();
        $statusArray = iterator_to_array($statuses);

        $this->assertSame('node1', $statusArray[0]->getId());
        $this->assertSame('node2', $statusArray[1]->getId());
        $this->assertSame('node3', $statusArray[2]->getId());
    }

    // ========================================
    // getNodeStatus() Tests
    // ========================================

    public function testGetNodeStatusReturnsStatusWhenExists(): void
    {
        $this->repo->setNodeStatus('node1', 'healthy');

        $status = $this->service->getNodeStatus('node1');

        $this->assertInstanceOf(Status::class, $status);
        $this->assertSame('node1', $status->getId());
        $this->assertSame('healthy', $status->getStatus());
    }

    public function testGetNodeStatusReturnsUnknownWhenNodeDoesNotExist(): void
    {
        $status = $this->service->getNodeStatus('nonexistent');

        $this->assertInstanceOf(Status::class, $status);
        $this->assertSame('nonexistent', $status->getId());
        $this->assertSame('unknown', $status->getStatus());
    }

    public function testGetNodeStatusReturnsCorrectStatusForDifferentStatuses(): void
    {
        $testCases = [
            ['nodeId' => 'healthy-node', 'status' => 'healthy'],
            ['nodeId' => 'unhealthy-node', 'status' => 'unhealthy'],
            ['nodeId' => 'maintenance-node', 'status' => 'maintenance'],
            ['nodeId' => 'unknown-node', 'status' => 'unknown'],
        ];

        foreach ($testCases as $testCase) {
            $this->repo->setNodeStatus($testCase['nodeId'], $testCase['status']);
        }

        foreach ($testCases as $testCase) {
            $status = $this->service->getNodeStatus($testCase['nodeId']);
            $this->assertSame($testCase['status'], $status->getStatus());
        }
    }

    // ========================================
    // setNodeStatus() Tests
    // ========================================

    public function testSetNodeStatusSuccessfully(): void
    {
        $this->service->setNodeStatus('node1', 'healthy');

        $status = $this->service->getNodeStatus('node1');
        $this->assertSame('healthy', $status->getStatus());
    }

    public function testSetNodeStatusUpdatesExistingStatus(): void
    {
        $this->service->setNodeStatus('node1', 'healthy');
        $this->service->setNodeStatus('node1', 'unhealthy');

        $status = $this->service->getNodeStatus('node1');
        $this->assertSame('unhealthy', $status->getStatus());
    }

    public function testSetNodeStatusWithAllAllowedStatuses(): void
    {
        $allowedStatuses = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

        foreach ($allowedStatuses as $index => $allowedStatus) {
            $nodeId = 'node' . $index;
            $this->service->setNodeStatus($nodeId, $allowedStatus);

            $status = $this->service->getNodeStatus($nodeId);
            $this->assertSame($allowedStatus, $status->getStatus());
        }
    }

    public function testSetNodeStatusThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status: invalid');

        $this->service->setNodeStatus('node1', 'invalid');
    }

    public function testSetNodeStatusThrowsExceptionForEmptyStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->setNodeStatus('node1', '');
    }

    // ========================================
    // Integration & Complex Scenarios
    // ========================================

    public function testCompleteStatusLifecycle(): void
    {
        // Node starts without status (should return 'unknown')
        $status = $this->service->getNodeStatus('lifecycle1');
        $this->assertSame('unknown', $status->getStatus());

        // Set initial status
        $this->service->setNodeStatus('lifecycle1', 'healthy');
        $status = $this->service->getNodeStatus('lifecycle1');
        $this->assertSame('healthy', $status->getStatus());

        // Update to maintenance
        $this->service->setNodeStatus('lifecycle1', 'maintenance');
        $status = $this->service->getNodeStatus('lifecycle1');
        $this->assertSame('maintenance', $status->getStatus());

        // Update to unhealthy
        $this->service->setNodeStatus('lifecycle1', 'unhealthy');
        $status = $this->service->getNodeStatus('lifecycle1');
        $this->assertSame('unhealthy', $status->getStatus());

        // Back to healthy
        $this->service->setNodeStatus('lifecycle1', 'healthy');
        $status = $this->service->getNodeStatus('lifecycle1');
        $this->assertSame('healthy', $status->getStatus());
    }

    public function testMultipleNodesWithDifferentStatuses(): void
    {
        $this->service->setNodeStatus('app1', 'healthy');
        $this->service->setNodeStatus('app2', 'healthy');
        $this->service->setNodeStatus('app3', 'unhealthy');
        $this->service->setNodeStatus('app4', 'maintenance');
        $this->service->setNodeStatus('app5', 'unknown');

        $statuses = $this->service->getStatuses();
        $statusArray = iterator_to_array($statuses);

        $this->assertCount(5, $statusArray);

        // Verify each status
        $statusMap = [];
        foreach ($statusArray as $status) {
            $statusMap[$status->getId()] = $status->getStatus();
        }

        $this->assertSame('healthy', $statusMap['app1']);
        $this->assertSame('healthy', $statusMap['app2']);
        $this->assertSame('unhealthy', $statusMap['app3']);
        $this->assertSame('maintenance', $statusMap['app4']);
        $this->assertSame('unknown', $statusMap['app5']);
    }

    public function testGetStatusesAfterMultipleUpdates(): void
    {
        // Create initial statuses
        $this->service->setNodeStatus('node1', 'healthy');
        $this->service->setNodeStatus('node2', 'unhealthy');

        // Update existing statuses
        $this->service->setNodeStatus('node1', 'maintenance');
        $this->service->setNodeStatus('node2', 'healthy');

        // Add new status
        $this->service->setNodeStatus('node3', 'unhealthy');

        $statuses = $this->service->getStatuses();
        $statusArray = iterator_to_array($statuses);

        $this->assertCount(3, $statusArray);

        $statusMap = [];
        foreach ($statusArray as $status) {
            $statusMap[$status->getId()] = $status->getStatus();
        }

        $this->assertSame('maintenance', $statusMap['node1']);
        $this->assertSame('healthy', $statusMap['node2']);
        $this->assertSame('unhealthy', $statusMap['node3']);
    }

    public function testStatusObjectImmutability(): void
    {
        $this->service->setNodeStatus('node1', 'healthy');

        $status1 = $this->service->getNodeStatus('node1');
        $status2 = $this->service->getNodeStatus('node1');

        // Both should have the same values
        $this->assertSame($status1->getId(), $status2->getId());
        $this->assertSame($status1->getStatus(), $status2->getStatus());

        // But should be different instances
        $this->assertNotSame($status1, $status2);
    }
}
