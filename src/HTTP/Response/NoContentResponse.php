<?php

declare(strict_types=1);

class NoContentResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(204, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}