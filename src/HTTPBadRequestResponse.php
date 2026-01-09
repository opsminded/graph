<?php

declare(strict_types=1);

class BadRequestResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(400, 'error', $message, $data);
    }
}