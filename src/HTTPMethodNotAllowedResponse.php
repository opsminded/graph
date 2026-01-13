<?php

declare(strict_types=1);

class HTTPMethodNotAllowedResponse extends HTTPResponse
{
    public function __construct(string $method)
    {
        return parent::__construct(405, 'error', "method '{$method}' not allowed", []);
    }
}