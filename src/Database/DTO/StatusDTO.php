<?php

declare(strict_types=1);

final class StatusDTO
{
    public function __construct(
        public string $node_id,
        public ?string $status
    ) {}
}