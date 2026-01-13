<?php

declare(strict_types=1);

final class ModelLog
{
    public string $entityType;
    public string $entityId;
    public string $action;
    public ?array $oldData;
    public ?array $newData;
    public string $userId;
    public string $ipAddress;
    public string $createdAt;

    public function __construct(string $entityType, string $entityId, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->action     = $action;
        $this->oldData    = $oldData;
        $this->newData    = $newData;
    }

    public function toArray(): array
    {
        return [
            'entityType' => $this->entityType,
            'entityId'   => $this->entityId,
            'action'     => $this->action,
            'oldData'    => $this->oldData,
            'newData'    => $this->newData,
            'userId'     => $this->userId,
            'ipAddress'  => $this->ipAddress,
            'createdAt'  => $this->createdAt,
        ];
    }
}