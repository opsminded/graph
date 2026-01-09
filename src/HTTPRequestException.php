<?php

declare(strict_types=1);

final class RequestException extends RuntimeException
{
    public array $data;
    public array $params;
    public string $path;
    
    public function __construct($message, array $data, array $params, string $path)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
        $this->params = $params;
        $this->path = $path;
    }
}