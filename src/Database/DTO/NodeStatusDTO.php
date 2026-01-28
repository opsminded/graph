<?php

declare(strict_types=1);

final class NodeStatusDTO
{
    public function __construct(
        public string $node_id,
        public ?string $status
    ) {}
}