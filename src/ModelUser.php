<?php

declare(strict_types=1);

final class ModelUser
{
    private string $id;
    private ModelGroup $group;

    public function __construct(string $id, ModelGroup $group)
    {
        $this->id = $id;
        $this->group = $group;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): ModelGroup
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
