<?php

declare(strict_types=1);

final class ModelStatus
{
    public const STATUS_KEYNAME_NODE_ID = "node_id";
    public const STATUS_KEYNAME_STATUS = "status";
    private const ALLOWED_NODE_STATUSES = ["unknown", "healthy", "unhealthy", "maintenance"];

    private string $nodeId;
    private string $status;

    public function __construct(string $nodeId, string $status)
    {
        if (!in_array($status, self::ALLOWED_NODE_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid node status: {$status}");
        }
        $this->nodeId = $nodeId;
        $this->status = $status;
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'node_id' => $this->nodeId,
            'status'  => $this->status,
        ];
    }
}