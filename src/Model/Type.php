<?php

declare(strict_types=1);

final class Type
{
    public string $id;
    public string $name;

    public const TYPE_KEYNAME_ID = "id";
    public const TYPE_KEYNAME_NAME = "name";

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function toArray(): array
    {
        return [
            self::TYPE_KEYNAME_ID => $this->id,
            self::TYPE_KEYNAME_NAME => $this->name,
        ];
    }
}