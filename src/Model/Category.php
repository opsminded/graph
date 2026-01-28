<?php

declare(strict_types=1);

final class Category
{

    public const CATEGORY_KEYNAME_ID     = "id";
    public const CATEGORY_KEYNAME_NAME   = "name";
    public const CATEGORY_KEYNAME_SHAPE  = "shape";
    public const CATEGORY_KEYNAME_WIDTH  = "width";
    public const CATEGORY_KEYNAME_HEIGHT = "height";

    private string $id;
    private string $name;
    private string $shape;
    private int $width;
    private int $height;

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
            self::CATEGORY_KEYNAME_ID     => $this->id,
            self::CATEGORY_KEYNAME_NAME   => $this->name,
            self::CATEGORY_KEYNAME_SHAPE  => $this->shape,
            (int)self::CATEGORY_KEYNAME_WIDTH  => $this->width,
            (int)self::CATEGORY_KEYNAME_HEIGHT => $this->height,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShape(): string
    {
        return $this->shape;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}