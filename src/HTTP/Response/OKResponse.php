<?php

declare(strict_types=1);

class OKResponse extends Response
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}
