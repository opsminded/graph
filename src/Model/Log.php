<?php

declare(strict_types=1);

final class Log
{

    public const LOG_KEYNAME_ENTITY_TYPE = "entityType";
    public const LOG_KEYNAME_ENTITY_ID   = "entityId";
    public const LOG_KEYNAME_ACTION      = "action";
    public const LOG_KEYNAME_OLD_DATA    = "oldData";
    public const LOG_KEYNAME_NEW_DATA    = "newData";
    public const LOG_KEYNAME_USER_ID     = "userId";
    public const LOG_KEYNAME_IP_ADDRESS  = "ipAddress";
    public const LOG_KEYNAME_CREATED_AT  = "createdAt";

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
            self::LOG_KEYNAME_ENTITY_TYPE => $this->entityType,
            self::LOG_KEYNAME_ENTITY_ID   => $this->entityId,
            self::LOG_KEYNAME_ACTION      => $this->action,
            self::LOG_KEYNAME_OLD_DATA    => $this->oldData,
            self::LOG_KEYNAME_NEW_DATA    => $this->newData,
            self::LOG_KEYNAME_USER_ID     => $this->userId,
            self::LOG_KEYNAME_IP_ADDRESS  => $this->ipAddress,
            self::LOG_KEYNAME_CREATED_AT  => $this->createdAt,
        ];
    }
}