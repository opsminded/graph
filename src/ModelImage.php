<?php

declare(strict_types=1);

final class ModelImage
{
    private string $name;
    private string $data;
    private string $etag;

    public function __construct(string $name, string $data, string $etag)
    {
        $this->name = $name;
        $this->data = $data;
        $this->etag = $etag;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): string
    {
        return $this->data;
    }
}