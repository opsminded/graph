<?php

declare(strict_types=1);

final class Logger implements LoggerInterface
{
    private int $level = 3;

    public const LOGGER_LEVEL_DEBUG = 1;
    public const LOGGER_LEVEL_INFO  = 2;
    public const LOGGER_LEVEL_ERROR = 3;

    private static array $levelNames = [
        self::LOGGER_LEVEL_DEBUG => 'DEBUG',
        self::LOGGER_LEVEL_INFO  => 'INFO',
        self::LOGGER_LEVEL_ERROR => 'ERROR',
    ];

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

    private function log(int $type, $message, $data = [])
    {
        if ($type < $this->level) {
            return;
        }

        $level = self::$levelNames[$type] ?? 'UNKNOWN';

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$level}] {$method}: $message ($data)\n";
        error_log($message);
    }
}