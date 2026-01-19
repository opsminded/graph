<?php

declare(strict_types=1);

class TestModelGraph extends TestAbstractTest
{
    public function testGraphConstructor(): void
    {
        $node1 = new ModelNode('node1', 'Node 01', 'business', 'server', false,  ['key' => 'value1']);
        $node2 = new ModelNode('node2', 'Node 02', 'application', 'database', false, ['key' => 'value2']);
        
        $edge1 = new ModelEdge('node1', 'node2', 'lbl node1', ['weight' => '10']);

        $graph = new ModelGraph([$node1, $node2], [$edge1]);
        
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
