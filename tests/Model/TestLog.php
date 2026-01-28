<?php

declare(strict_types=1);

class TestLog extends TestAbstractTest
{
    public function testLogConstructor(): void
    {
        $oldData = ['id' => 'node1', 'label' => 'Old Label'];
        $newData = ['id' => 'node1', 'label' => 'New Label'];

        $log = new Log('node', 'node1', 'update', $oldData, $newData, 'user123', 'ip_address', new DateTimeImmutable());

        if ($log->getEntityType() != 'node' || $log->getEntityId() != 'node1' || $log->getAction() != 'update') {
            throw new Exception('test_AuditLog problem - property mismatch');
        }

        if ($log->getOldData()['label'] != 'Old Label') {
            throw new Exception('test_AuditLog problem - oldData mismatch');
        }

        if ($log->getNewData()['label'] != 'New Label') {
            throw new Exception('test_AuditLog problem - newData mismatch');
        }

        // Test with null data
        $log2 = new Log('node', 'node2', 'insert', null, ['id' => 'node2'], 'user123', 'ip_address', new DateTimeImmutable());
        if ($log2->getOldData() !== null) {
            throw new Exception('test_AuditLog problem - oldData should be null');
        }

        if ($log2->getNewData()['id'] != 'node2') {
            throw new Exception('test_AuditLog problem - newData mismatch for insert');
        }

        // Test delete action with null newData
        $log3 = new Log('edge', 'edge1', 'delete', ['id' => 'edge1'], null, 'user123', 'ip_address', new DateTimeImmutable());
        if ($log3->getNewData() !== null) {
            throw new Exception('test_AuditLog problem - newData should be null for delete');
        }

        if ($log3->getOldData()['id'] != 'edge1') {
            throw new Exception('test_AuditLog problem - oldData mismatch for delete');
        }
    }
}
