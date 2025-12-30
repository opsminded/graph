<?php
namespace Opsminded\Graph;

class UndirectedGraph extends Graph
{
    public function addEdge(Edge $edge): void
    {
        parent::addEdge($edge);
        // add reverse edge for undirected behaviour
        $reverse = new Edge($edge->to(), $edge->from(), $edge->weight());
        $this->adj[$edge->to()->getId()][] = $reverse;
    }
}
