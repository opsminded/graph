<?php

declare(strict_types=1);

class TestHelperCytoscape extends TestAbstractTest
{
    public function testHelperCytoscape(): void
    {
        $img = new HelperImages();
        $cy = new HelperCytoscape($img);

        $nodes = [
            new ModelNode('n1', 'Node 1', 'business', 'server', ['a' => 1]),
            new ModelNode('n2', 'Node 2', 'business', 'server', ['b' => 2]),
            new ModelNode('n3', 'Node 3', 'business', 'server', ['c' => 3]),
        ];

        $edges = [
            new ModelEdge('n1', 'n2'),
            new ModelEdge('n2', 'n3'),
        ];

        $graph = new ModelGraph($nodes, $edges);
        $data = $cy->toArray($graph);
        print_r($data);
        exit();
    }
}
