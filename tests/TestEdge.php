<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';
require_once dirname(__DIR__) . '/src/Edge.php';

class TestEdge extends AbstractTest
{
    public function testEdgeConstruct(): void
    {
        $edge = new Edge('node1', 'node2', ['weight' => '10']);

        if ($edge->getId() != 'node1-node2' || $edge->getSource() != 'node1' || $edge->getTarget() != 'node2') {
            throw new Exception('testEdgeConstruct');
        }

        $data = $edge->getData();
        if ($data['weight'] != '10') {
            throw new Exception('testEdgeConstruct');
        }

        $arr = $edge->toArray();
        if ($arr['source'] != 'node1' || $arr['target'] != 'node2') {
            throw new Exception('testEdgeConstruct');
        }

        if ($arr['data']['weight'] != '10') {
            throw new Exception('testEdgeConstruct');
        }

        // Test with empty data
        $edge3 = new Edge('node5', 'node6');
        if (count($edge3->getData()) != 0) {
            throw new Exception('testEdgeConstruct');
        }
    }
}
