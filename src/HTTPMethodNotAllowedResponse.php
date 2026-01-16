<?php

declare(strict_types=1);

class HTTPMethodNotAllowedResponse extends HTTPResponse
{
    public function __construct(string $method, string $classMethod)
    {
        return parent::__construct(405, HTTPResponseInterface::VALUE_STATUS_ERROR, "method '{$method}' not allowed in '{$classMethod}'", ['method' => $method, 'location' => $classMethod]);
    }
}