<?php

declare(strict_types=1);

final class ModelGroup
{
    private const GROUP_KEYNAME_ID = "id";

    private const VALUE_ANONYMOUS   = "anonymous";
    private const VALUE_CONSUMER    = "consumer";
    private const VALUE_CONTRIBUTOR = "contributor";
    private const VALUE_ADMIN       = "admin";

    private const ALLOWED_GROUPS = [
        self::VALUE_ANONYMOUS,
        self::VALUE_CONSUMER,
        self::VALUE_CONTRIBUTOR,
        self::VALUE_ADMIN,
    ];
    
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
            self::GROUP_KEYNAME_ID => $this->id
        ];
    }
}