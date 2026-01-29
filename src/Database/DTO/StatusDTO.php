<?php

declare(strict_types=1);

final class StatusDTO
{
    public function __construct(
        public readonly string $node_id,
        public readonly ?string $status
    ) {
    }
}