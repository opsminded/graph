<?php

declare(strict_types=1);

class InternalServerErrorResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(500, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}