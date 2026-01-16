<?php

declare(strict_types=1);

class HTTPOKResponse extends HTTPResponse
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, HTTPResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}
