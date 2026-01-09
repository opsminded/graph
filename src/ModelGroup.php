<?php

declare(strict_types=1);

final class Group
{
    private const ALLOWED_GROUPS = ['anonymous', 'consumer', 'contributor', 'admin'];
    
    private string $id;
    
    public function __construct(string $id)
    {
        if (!in_array($id, self::ALLOWED_GROUPS, true)) {
            throw new InvalidArgumentException("Invalid user group: {$id}");
        }
        $this->id  = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id
        ];
    }
}