<?php

declare(strict_types=1);

class InternalServerErrorResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(500, 'error', $message, $data);
    }
}