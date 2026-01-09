<?php

declare(strict_types=1);

interface LoggerInterface
{
    public function info(string $message, array $data = []): void;
    public function debug(string $message, array $data = []): void;
    public function error(string $message, array $data = []): void;
}