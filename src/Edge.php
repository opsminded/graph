<?php
namespace Opsminded\Graph;

class Edge
{
    private Node $from;
    private Node $to;
    private float $weight;

    public function __construct(Node $from, Node $to, float $weight = 1.0)
    {
        $this->from = $from;
        $this->to = $to;
        $this->weight = $weight;
    }

    public function from(): Node
    {
        return $this->from;
    }

    public function to(): Node
    {
        return $this->to;
    }

    public function weight(): float
    {
        return $this->weight;
    }
}
