<?php

declare(strict_types=1);

final class Logger implements LoggerInterface
{
    private string $fileName;
    private $fd;

    public function __construct($file_name)
    {
        $this->fileName = $file_name;
        $this->fd = fopen($this->fileName, 'a');
    }

    public function __destruct()
    {
        @fclose($this->fd);
    }

    public function info(string $message, array $data = []): void
    {
        $this->log('INFO', $message, $data);
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log('DEBUG', $message, $data);
    }

    public function error(string $message, array $data = []): void
    {
        $this->log('ERROR', $message, $data);
    }

    private function log(string $type, $message, $data = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$type}] {$method}: $message ($data)\n";
        fwrite($this->fd, $message);
    }
}