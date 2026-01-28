<?php

declare(strict_types=1);

final class ServiceException extends RuntimeException
{
    public function __construct(string $message, PDOException $exception)
    {
        $message = "Service Error: " . $message  .". Exception: ". $exception->getMessage();
        parent::__construct($message, 0, $exception);
    }
}
