<?php

declare(strict_types=1);

class DatabaseException extends Exception
{
    public function __construct(string $message = "A database error occurred.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}