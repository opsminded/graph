<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

final class Edge
{
    private string $from;
    private string $to;
    private array $data;

    public function __construct(string $from, string $to, array $data = [])
    {
        $this->from = $from;
        $this->to   = $to;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->from . ':' . $this->to;
    }

    public function getFromNodeId(): string
    {
        return $this->from;
    }

    public function getToNodeId(): string
    {
        return $this->to;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'id'   => $this->getId(),
            'from' => $this->from,
            'to'   => $this->to,
            'data' => $this->data,
        ];
    }
}
