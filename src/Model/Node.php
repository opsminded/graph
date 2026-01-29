<?php

declare(strict_types=1);

final class Node
{
    public const ID_VALIDATION_REGEX = "/^[a-zA-Z0-9\-_]+$/";
    public const LABEL_MAX_LENGTH    = 120;
    
    private string $id;
    private string $label;
    private string $categoryID;
    private string $typeID;
    private bool $userCreated;

    private array $data = [];

    public function __construct(string $id, string $label, string $categoryID, string $typeID, bool $userCreated, array $data)
    {
        $this->validate($id, $label);
        $this->id         = $id;
        $this->label      = $label;
        $this->categoryID = $categoryID;
        $this->typeID     = $typeID;
        $this->userCreated = $userCreated;
        $this->data       = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCategory(): string
    {
        return $this->categoryID;
    }

    public function getType(): string
    {
        return $this->typeID;
    }

    public function getUserCreated(): bool
    {
        return $this->userCreated;
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function validate(string $id, string $label): void
    {
        if (!preg_match(self::ID_VALIDATION_REGEX, $id)) {
            throw new InvalidArgumentException("Invalid node ID: {$id}");
        }

        if (strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException("Node label exceeds maximum length of " . self::LABEL_MAX_LENGTH);
        }
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'category' => $this->categoryID,
            'type'     => $this->typeID,
            'user_created' => $this->userCreated,
            'data'     => $this->data
        ];
    }
}