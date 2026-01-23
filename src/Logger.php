<?php

declare(strict_types=1);

final class Logger implements LoggerInterface
{
    private int $level = 3;

    public const LOGGER_LEVEL_INFO = "INFO";
    public const LOGGER_LEVEL_DEBUG = "DEBUG";
    public const LOGGER_LEVEL_ERROR = "ERROR";

    public function __construct(int $level = 3)
    {
        $this->level = $level;
    }

    public function info(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_INFO, $message, $data);
        
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_DEBUG, $message, $data);
    }

    public function error(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_ERROR, $message, $data);
    }

    private function log(string $type, $message, $data = [])
    {
        if ($this->level == 3 && $type != self::LOGGER_LEVEL_ERROR) {
            return;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$type}] {$method}: $message ($data)\n";
        error_log($message);
    }
}