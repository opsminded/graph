<?php

declare(strict_types=1);

final class ModelProject
{
    const PROJECT_KEYNAME_ID = "id";
    const PROJECT_KEYNAME_NAME = "name";
    const PROJECT_KEYNAME_AUTHOR = "author";
    const PROJECT_KEYNAME_CREATED_AT = "created_at";
    const PROJECT_KEYNAME_UPDATED_AT = "updated_at";
    const PROJECT_KEYNAME_NODES = "nodes";
    
    public string $id;
    public string $name;
    public string $author;
    public DateTimeImmutable $createdAt;
    public DateTimeImmutable $updatedAt;
    
    public array $nodes = [];

    public function __construct(
        string $id,
        string $name,
        string $author,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $nodes
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->nodes = $nodes;
    }

    public function toArray(): array
    {
        return [
            self::PROJECT_KEYNAME_ID => $this->id,
            self::PROJECT_KEYNAME_NAME => $this->name,
            self::PROJECT_KEYNAME_AUTHOR => $this->author,
            self::PROJECT_KEYNAME_CREATED_AT => $this->createdAt->format(DateTime::ATOM),
            self::PROJECT_KEYNAME_UPDATED_AT => $this->updatedAt->format(DateTime::ATOM),
            self::PROJECT_KEYNAME_NODES => $this->nodes,
        ];
    }
}