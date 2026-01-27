<?php

declare(strict_types=1);

class NotFoundResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(404, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}