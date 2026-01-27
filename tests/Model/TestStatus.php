<?php

declare(strict_types=1);

class TestStatus extends TestAbstractTest
{
    public function testStatusConstructor(): void
    {
        $status = new Status('node1', 'healthy');

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
            new Status('node1', 'xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }

        throw new Exception(('problem on testStatusException'));
    }
}