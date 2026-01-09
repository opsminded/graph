<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';
require_once dirname(__DIR__) . '/src/Graph.php';

class TestGraph extends AbstractTest
{
    public function testGraphConstructor(): void
    {
        $node1 = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value1']);
        $node2 = new Node('node2', 'Node 02', 'application', 'database', ['key' => 'value2']);
        
        $edge1 = new Edge('node1', 'node2', ['weight' => '10']);

        $graph = new Graph([$node1, $node2], [$edge1]);
        
        if (count($graph->getNodes()) != 2) {
            throw new Exception('TODO message. Node quantity');
        }

        if (count($graph->getEdges()) != 1) {
            throw new Exception('TODO message. Edge quantity');
        }

        $data = $graph->toArray();
        if (!isset($data['nodes']) || !isset($data['edges'])) {
            throw new Exception('TODO message');
        }

        if (count($data['nodes']) != 2 || count($data['edges']) != 1) {
            throw new Exception('TODO message');
        }
    }
}
