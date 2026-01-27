<?php

declare(strict_types=1);

class CreatedResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(201, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}
