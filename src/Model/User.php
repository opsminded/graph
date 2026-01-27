<?php

declare(strict_types=1);

final class User
{
    private string $id;
    private Group $group;

    public const USER_KEYNAME_ID = "id";
    public const USER_KEYNAME_GROUP = "group";

    public function __construct(string $id, Group $group)
    {
        $this->id = $id;
        $this->group = $group;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function toArray(): array
    {
        return [
            self::USER_KEYNAME_ID    => $this->id,
            self::USER_KEYNAME_GROUP => $this->group->toArray(),
        ];
    }
}
