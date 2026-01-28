<?php

declare(strict_types=1);

final class Project
{
    const PROJECT_KEYNAME_ID = "id";
    const PROJECT_KEYNAME_NAME = "name";
    const PROJECT_KEYNAME_AUTHOR = "author";
    const PROJECT_KEYNAME_CREATED_AT = "created_at";
    const PROJECT_KEYNAME_UPDATED_AT = "updated_at";
    const PROJECT_KEYNAME_GRAPH = "graph";
    
    private string $id;
    private string $name;
    private string $author;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    
    private ?Graph $graph;

    private array $data = [];

    public function __construct(
        string $id,
        string $name,
        string $author,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?Graph $graph,
        array $data = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->graph = $graph;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getGraph(): ?Graph
    {
        return $this->graph;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            self::PROJECT_KEYNAME_ID => $this->id,
            self::PROJECT_KEYNAME_NAME => $this->name,
            self::PROJECT_KEYNAME_AUTHOR => $this->author,
            self::PROJECT_KEYNAME_CREATED_AT => $this->createdAt->format(DateTime::ATOM),
            self::PROJECT_KEYNAME_UPDATED_AT => $this->updatedAt->format(DateTime::ATOM),
            self::PROJECT_KEYNAME_GRAPH => $this->graph,
        ];
    }
}