<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\NodeStatus;

class NodeStatusTest extends TestCase
{
    public function testGettersAndToArray()
    {
        $ns = new NodeStatus('node-1', 'ok', '2025-12-30 12:00:00');

        $this->assertSame('node-1', $ns->getNodeId());
        $this->assertSame('ok', $ns->getStatus());
        $this->assertSame('2025-12-30 12:00:00', $ns->getCreatedAt());

        $arr = $ns->toArray();
        $this->assertSame('node-1', $arr['node_id']);
        $this->assertSame('ok', $arr['status']);
        $this->assertSame('2025-12-30 12:00:00', $arr['created_at']);
    }
}
