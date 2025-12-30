<?php
namespace Opsminded\Graph;

class Edge
{
    private Node $from;
    private Node $to;
    private float $weight;
    private string $id;

    public function __construct(Node $from, Node $to, float $weight = 1.0, ?string $id = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->weight = $weight;
        $this->id = $id ?? md5($from->getId() . '->' . $to->getId());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return ['source' => $this->from->getId(), 'target' => $this->to->getId(), 'weight' => $this->weight];
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
