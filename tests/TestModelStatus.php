<?php

declare(strict_types=1);

class TestModelStatus extends TestAbstractTest
{
    public function testStatusConstructor(): void
    {
        $status = new ModelStatus('node1', 'healthy');

        if($status->getNodeId() != 'node1' || $status->getStatus() != 'healthy') {
            throw new Exception('testStatusConstructor problem');
        }

        $data = $status->toArray();
        if($data['node_id'] != 'node1' || $data['status'] != 'healthy') {
            throw new Exception('testStatusConstructor problem');
        }
    }

    public function testStatusException(): void
    {
        try {
            new ModelStatus('node1', 'xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }

        throw new Exception(('problem on testStatusException'));
    }
}