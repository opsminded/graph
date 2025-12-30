<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Database;

class DatabaseTest extends TestCase
{
    private string $dbFile;
    private Database $db;

    protected function setUp(): void
    {
        $this->dbFile = sys_get_temp_dir() . '/graph_test_' . uniqid() . '.db';
        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
        $this->db = new Database($this->dbFile);
    }

    protected function tearDown(): void
    {
        $backupDir = dirname($this->dbFile) . '/backups';
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . '/*') as $f) {
                @unlink($f);
            }
            @rmdir($backupDir);
        }

        if (file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
    }

    public function testNodeLifecycle()
    {
        $id = 'n1';
        $data = ['name' => 'node1'];

        $this->assertFalse($this->db->nodeExists($id));
        $this->assertTrue($this->db->insertNode($id, $data));
        $this->assertTrue($this->db->nodeExists($id));

        $f = $this->db->getNode($id);
        $this->assertSame('node1', $f['name']);

        $this->assertEquals(1, $this->db->updateNode($id, ['name' => 'node1b']));
        $this->assertSame('node1b', $this->db->getNode($id)['name']);

        [$count, $old] = $this->db->deleteNode($id);
        $this->assertEquals(1, $count);
    }

    public function testEdgeLifecycle()
    {
        $a = 'a';
        $b = 'b';
        $this->db->insertNode($a, ['id' => $a]);
        $this->db->insertNode($b, ['id' => $b]);

        $this->assertFalse($this->db->edgeExists($a, $b));

        $this->assertTrue($this->db->insertEdge($a, $b));
        $this->assertTrue($this->db->edgeExists($a, $b));
        $this->assertTrue($this->db->edgeExists($b, $a));

        // Library no longer provides updateEdge; simulate update by deleting and inserting
        [$cntBefore,] = $this->db->deleteEdge($a, $b);
        $this->assertEquals(1, $cntBefore);
        $this->assertTrue($this->db->insertEdge($a, $b));

        [$cnt, $old] = $this->db->deleteEdge($a, $b);
        $this->assertEquals(1, $cnt);
    }

    public function testAuditAndStatus()
    {
        $this->db->insertNode('nX', ['id' => 'nX']);
        $this->assertTrue($this->db->insertAuditLog('node', 'nX', 'create', null, ['id' => 'nX'], 'u1', '1.1.1.1'));

        $logs = $this->db->fetchAuditHistory('node', 'nX');
        $this->assertNotEmpty($logs);

        $first = $logs[0];
        $this->assertSame('node', $first['entity_type']);

        $this->assertTrue($this->db->insertNodeStatus('nX', 'ok'));
        $latest = $this->db->fetchLatestNodeStatus('nX');
        $this->assertSame('ok', $latest['status']);

        $history = $this->db->fetchNodeStatusHistory('nX');
        $this->assertNotEmpty($history);

        $allLatest = $this->db->fetchAllLatestStatuses();
        $this->assertIsArray($allLatest);
    }

    public function testInsertOrIgnoreAndFetchAll()
    {
        $this->db->insertNode('nx', ['id' => 'nx', 'v' => 1]);
        $this->db->insertNode('nx', ['id' => 'nx', 'v' => 2]);

        $nodes = $this->db->nodes();
        $this->assertNotEmpty($nodes);

        // `insertEdge` is idempotent (INSERT OR IGNORE); calling twice is safe
        $this->db->insertEdge('nx', 'nx');
        $this->db->insertEdge('nx', 'nx');

        $edges = $this->db->edges();
        $this->assertNotEmpty($edges);
    }

    public function testDeleteEdgesFromAndByNode()
    {
        $this->db->insertNode('a1', ['id' => 'a1']);
        $this->db->insertNode('b1', ['id' => 'b1']);
        $this->db->insertEdge('a1', 'b1');
        $this->db->insertEdge('a1', 'b1');

        $deleted = $this->db->deleteEdgesFrom('a1');
        $this->assertCount(1, $deleted);

        // Insert again and delete by node
        $this->db->insertEdge('a1', 'b1');
        $deleted2 = $this->db->deleteEdgesByNode('a1');
        $this->assertNotEmpty($deleted2);
    }

    public function testFetchAuditByIdAndAfterTimestamp()
    {
        $this->db->insertNode('z1', ['id' => 'z1']);
        $this->db->insertAuditLog('node', 'z1', 'create', null, ['id' => 'z1'], 'u', '1.1.1.1');

        $logs = $this->db->fetchAuditHistory('node', 'z1');
        $this->assertNotEmpty($logs);

        $log = $this->db->fetchAuditLogById((int)$logs[0]['id'], 'node', 'z1');
        $this->assertNotNull($log);

        $after = $this->db->fetchAuditLogsAfterTimestamp('2000-01-01 00:00:00');
        $this->assertNotEmpty($after);
    }

    public function testUpdateEdgeAndTransactions()
    {
        $this->db->insertNode('nA', ['id' => 'nA']);
        $this->db->insertNode('nB', ['id' => 'nB']);
        $this->db->insertEdge('nA', 'nB');

        // No updateEdge method — replace by delete + insert to simulate update
        [$cntDel,] = $this->db->deleteEdge('nA', 'nB');
        $this->assertEquals(1, $cntDel);
        $this->assertTrue($this->db->insertEdge('nA', 'nB'));

        $this->assertTrue($this->db->beginTransaction());
        $this->assertTrue($this->db->commit());
        $this->assertTrue($this->db->beginTransaction());
        $this->assertTrue($this->db->rollBack());
    }
}
