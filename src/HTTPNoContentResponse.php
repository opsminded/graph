<?php

declare(strict_types=1);

class HTTPNoContentResponse extends HTTPResponse
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(204, HTTPResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}