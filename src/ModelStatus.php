<?php

declare(strict_types=1);

final class ModelStatus
{
    public const STATUS_VALUE_UNKNOWN     = "unknown";
    public const STATUS_VALUE_HEALTHY     = "healthy";
    public const STATUS_VALUE_UNHEALTHY   = "unhealthy";
    public const STATUS_VALUE_MAINTENANCE = "maintenance";
    public const STATUS_VALUE_IMPACTED    = "impacted";

    public const STATUS_KEYNAME_NODE_ID = "node_id";
    public const STATUS_KEYNAME_STATUS = "status";
    
    private const ALLOWED_NODE_STATUSES = [
        self::STATUS_VALUE_UNKNOWN,
        self::STATUS_VALUE_HEALTHY,
        self::STATUS_VALUE_UNHEALTHY,
        self::STATUS_VALUE_MAINTENANCE,
        self::STATUS_VALUE_IMPACTED,
    ];

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
            self::STATUS_KEYNAME_NODE_ID => $this->nodeId,
            self::STATUS_KEYNAME_STATUS  => $this->status,
        ];
    }
}