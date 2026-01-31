<?php

declare(strict_types=1);

final class ProjectNodeDTO
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $nodeId
    ) {
    }
}