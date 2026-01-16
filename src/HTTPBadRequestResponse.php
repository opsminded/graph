<?php

declare(strict_types=1);

class HTTPBadRequestResponse extends HTTPResponse
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(400, HTTPResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}