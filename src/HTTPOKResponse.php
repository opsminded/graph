<?php

declare(strict_types=1);

class OKResponse extends HTTPResponse
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, 'success', $message, $data);
    }
}
