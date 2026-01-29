<?php

declare(strict_types=1);

final class LogDTO
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $action,
        public readonly ?array $oldData,
        public readonly ?array $newData,
        public readonly string $userId,
        public readonly string $ipAddress,
        public readonly DateTimeImmutable $timestamp,
    ) {
    }
}