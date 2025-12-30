<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\UndirectedGraph;
use Opsminded\Graph\Node;
use Opsminded\Graph\Edge;
use Opsminded\Graph\Algorithms\BFS;

class GraphTest extends TestCase
{
    public function testBfsVisitsAllNodes()
    {
        $g = new UndirectedGraph();
        $a = new Node('a');
        $b = new Node('b');
        $c = new Node('c');

        $g->addEdge(new Edge($a, $b));
        $g->addEdge(new Edge($b, $c));

        $visited = [];
        BFS::traverse($g, 'a', function($n) use (&$visited) {
            $visited[] = $n->getId();
        });

        $this->assertEqualsCanonicalizing(['a','b','c'], $visited);
    }
}
