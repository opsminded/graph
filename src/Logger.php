<?php

declare(strict_types=1);

final class Logger implements LoggerInterface
{
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
        error_log($message);
    }
}