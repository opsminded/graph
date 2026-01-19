<?php

declare(strict_types=1);

final class HTTPRenderer
{
    private array $templates;
    
    public function __construct(array $templates)
    {
        $this->templates = $templates;
    }

    public function render(HTTPResponse $response): void
    {
    }
}