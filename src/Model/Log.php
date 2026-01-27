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