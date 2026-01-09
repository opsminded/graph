<?php

declare(strict_types=1);

final class ModelNode
{
    public const ALLOWED_CATEGORIES  = ['business', 'application', 'network', 'infrastructure'];
    public const ALLOWED_TYPES       = ['server', 'database', 'application'];
    public const ID_VALIDATION_REGEX = '/^[a-zA-Z0-9\-_]+$/';
    public const LABEL_MAX_LENGTH    = 20;
    
    private string $id;
    private string $label;
    private string $category;
    private string $type;

    private array $data = [];

    public function __construct(string $id, string $label, string $category, string $type, array $data)
    {
        $this->validate($id, $label, $category, $type);
        $this->id       = $id;
        $this->label    = $label;
        $this->category = $category;
        $this->type     = $type;
        $this->data     = $data;
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
        return $this->category;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function validate(string $id, string $label, string $category, string $type): void
    {
        if (!preg_match(self::ID_VALIDATION_REGEX, $id)) {
            throw new InvalidArgumentException("Invalid node ID: {$id}");
        }

        if (strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException("Node label exceeds maximum length of " . self::LABEL_MAX_LENGTH);
        }

        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            throw new InvalidArgumentException("Invalid node category: {$category}");
        }

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Invalid node type: {$type}");
        }
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'category' => $this->category,
            'type'     => $this->type,
            'data'     => $this->data
        ];
    }
}