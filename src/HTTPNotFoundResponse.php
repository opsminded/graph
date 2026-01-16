<?php

declare(strict_types=1);

class HTTPNotFoundResponse extends HTTPResponse
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(404, HTTPResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}