<?php

declare(strict_types=1);

class CreatedResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(201, 'success', $message, $data);
    }
}
