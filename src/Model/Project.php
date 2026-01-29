<?php

declare(strict_types=1);

final class Project
{
    private string $id;
    private string $name;
    private string $author;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;    
    private array $data = [];

    public function __construct(
        string $id,
        string $name,
        string $author,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $data = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
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

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'author' => $this->author,
            'created_at' => $this->createdAt->format(DateTime::ATOM),
            'updated_at' => $this->updatedAt->format(DateTime::ATOM),
            'data' => $this->data,
        ];
    }
}