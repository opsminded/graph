<?php

declare(strict_types=1);

namespace Opsminded\Graph;

final class NodeStatus
{
    private string $node_id;
    private string $status;
    private string $created_at;

    public function __construct(string $node_id, string $status, string $created_at)
    {
        $this->node_id    = $node_id;
        $this->status     = $status;
        $this->created_at = $created_at;
    }

    public function getNodeId(): string
    {
        return $this->node_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function toArray(): array
    {
        return [
            'node_id'    => $this->node_id,
            'status'     => $this->status,
            'created_at' => $this->created_at
        ];
    }
}
