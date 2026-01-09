<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';

class TestLog extends AbstractTest
{
    public function testLogConstructor(): void
    {
        $oldData = ['id' => 'node1', 'label' => 'Old Label'];
        $newData = ['id' => 'node1', 'label' => 'New Label'];

        $log = new Log('node', 'node1', 'update', $oldData, $newData);

        if ($log->entityType != 'node' || $log->entityId != 'node1' || $log->action != 'update') {
            throw new Exception('test_AuditLog problem - property mismatch');
        }

        if ($log->oldData['label'] != 'Old Label') {
            throw new Exception('test_AuditLog problem - oldData mismatch');
        }

        if ($log->newData['label'] != 'New Label') {
            throw new Exception('test_AuditLog problem - newData mismatch');
        }

        // Test with null data
        $log2 = new Log('node', 'node2', 'insert', null, ['id' => 'node2']);
        if ($log2->oldData !== null) {
            throw new Exception('test_AuditLog problem - oldData should be null');
        }

        if ($log2->newData['id'] != 'node2') {
            throw new Exception('test_AuditLog problem - newData mismatch for insert');
        }

        // Test delete action with null newData
        $log3 = new Log('edge', 'edge1', 'delete', ['id' => 'edge1'], null);
        if ($log3->newData !== null) {
            throw new Exception('test_AuditLog problem - newData should be null for delete');
        }

        if ($log3->oldData['id'] != 'edge1') {
            throw new Exception('test_AuditLog problem - oldData mismatch for delete');
        }
    }
}
