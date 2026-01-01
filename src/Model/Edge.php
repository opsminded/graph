<?php

declare(strict_types=1);

namespace Opsminded\Graph\Model;

final class Edge
{
    private string $source;
    private string $target;
    private array $data;

    public function __construct(string $source, string $target, array $data = [])
    {
        $this->source = $source;
        $this->target = $target;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return $this->data['id'];
    }

    public function getSourceNodeId(): string
    {
        return $this->source;
    }

    public function getTargetNodeId(): string
    {
        return $this->target;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
