<?php

declare(strict_types=1);

class HTTPInternalServerErrorResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(500, 'error', $message, $data);
    }
}