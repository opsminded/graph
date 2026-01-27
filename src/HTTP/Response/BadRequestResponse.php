<?php

declare(strict_types=1);

class BadRequestResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(400, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}