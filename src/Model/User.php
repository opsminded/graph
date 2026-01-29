<?php

declare(strict_types=1);

final class User
{
    private string $id;
    private Group $group;

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
            'id'    => $this->id,
            'group' => $this->group->toArray(),
        ];
    }
}
