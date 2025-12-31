<?php

declare(strict_types=1);

namespace Opsminded\Graph;

class Logger
{
    private $fd;
    public function __construct(string $filename)
    {
        $this->fd = fopen($filename, 'a');
        if ($this->fd === false) {
            throw new \RuntimeException("Could not open log file: " . $filename);
        }
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log('DEBUG ' . $message, $data);
    }

    public function info(string $message, array $data = []): void
    {
        $this->log('INFO ' . $message, $data);
    }

    public function log(string $message, array $data = []): void
    {
        $message = date('Y-m-d H:i:s') . ' ' . $message . ' (' . json_encode($data, JSON_UNESCAPED_UNICODE) . ')' . PHP_EOL;
        fwrite($this->fd, $message);
    }
}
