<?php

declare(strict_types=1);

final class ModelSave
{
    const SAVE_KEYNAME_ID = "id";
    const SAVE_KEYNAME_NAME = "name";
    const SAVE_KEYNAME_CREATOR = "creator";
    const SAVE_KEYNAME_CREATED_AT = "created_at";
    const SAVE_KEYNAME_UPDATED_AT = "updated_at";
    const SAVE_KEYNAME_NODES = "nodes";
    
    public string $id;
    public string $name;
    public string $creator;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    
    public array $nodes = [];

    public function __construct(
        string $id,
        string $name,
        string $creator,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $nodes
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->creator = $creator;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->nodes = $nodes;
    }

    public function toArray(): array
    {
        return [
            self::SAVE_KEYNAME_ID => $this->id,
            self::SAVE_KEYNAME_NAME => $this->name,
            self::SAVE_KEYNAME_CREATOR => $this->creator,
            self::SAVE_KEYNAME_CREATED_AT => $this->createdAt->format(DateTime::ATOM),
            self::SAVE_KEYNAME_UPDATED_AT => $this->updatedAt->format(DateTime::ATOM),
            self::SAVE_KEYNAME_NODES => $this->nodes,
        ];
    }
}