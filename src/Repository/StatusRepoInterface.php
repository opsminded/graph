<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

interface StatusRepoInterface
{
    public const ALLOWED_STATUSES   = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

    public function getStatuses(): array;
    public function getNodeStatus(string $id): string;
    public function setNodeStatus(string $id, string $status): void;
}
