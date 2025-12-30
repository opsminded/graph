<?php
namespace Opsminded\Graph\Algorithms;

use Opsminded\Graph\Graph;
use SplQueue;

class BFS
{
    public static function traverse(Graph $graph, string $startId, callable $visit): void
    {
        $start = $graph->getNode($startId);
        if (!$start) {
            return;
        }

        $queue = new SplQueue();
        $visited = [];

        $queue->enqueue($start);
        $visited[$startId] = true;

        while (!$queue->isEmpty()) {
            $node = $queue->dequeue();
            $visit($node);
            foreach ($graph->neighbors($node->getId()) as $edge) {
                $nid = $edge->to()->getId();
                if (!isset($visited[$nid])) {
                    $visited[$nid] = true;
                    $queue->enqueue($graph->getNode($nid));
                }
            }
        }
    }
}
