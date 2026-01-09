<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';
require_once dirname(__DIR__) . '/src/Status.php';

class TestStatus extends AbstractTest
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
}