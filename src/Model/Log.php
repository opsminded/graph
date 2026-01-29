<?php

declare(strict_types=1);

final class Log
{

    private string            $entityType;
    private string            $entityId;
    private string            $action;
    private ?array            $oldData;
    private ?array            $newData;
    private string            $userId;
    private string            $ipAddress;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldData = null,
        ?array $newData = null,
        string $userId,
        string $ipAddress,
        DateTimeImmutable $createdAt
    ) {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->action     = $action;
        $this->oldData    = $oldData;
        $this->newData    = $newData;
        $this->userId     = $userId;
        $this->ipAddress  = $ipAddress;
        $this->createdAt  = $createdAt;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getOldData(): ?array
    {
        return $this->oldData;
    }

    public function getNewData(): ?array
    {
        return $this->newData;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'entityType' => $this->entityType,
            'entityId'   => $this->entityId,
            'action'      => $this->action,
            'oldData'    => $this->oldData,
            'newData'    => $this->newData,
            'userId'     => $this->userId,
            'ipAddress'  => $this->ipAddress,
            'createdAt'  => $this->createdAt,
        ];
    }
}