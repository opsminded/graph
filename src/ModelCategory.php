<?php

declare(strict_types=1);

final class ModelCategory
{
    public string $id;
    public string $name;
    public string $shape;
    public int $width;
    public int $height;

    public function __construct(string $id, string $name, string $shape, int $width, int $height)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shape = $shape;
        $this->width = $width;
        $this->height = $height;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shape' => $this->shape,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}