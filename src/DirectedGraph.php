<?php
namespace Opsminded\Graph;

class DirectedGraph extends Graph
{
    public function addEdge(Edge $edge): void
    {
        parent::addEdge($edge);
    }
}
