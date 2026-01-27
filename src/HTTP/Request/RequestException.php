<?php

declare(strict_types=1);

final class RequestException extends RuntimeException
{
    private array $data;
    private array $params;
    private string $path;
    
    public function __construct($message, array $data, array $params, string $path)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
        $this->params = $params;
        $this->path = $path;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}