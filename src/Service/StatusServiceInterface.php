<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Model\Status;
use Opsminded\Graph\Model\Statuses;

interface StatusServiceInterface
{
    public function getStatuses(): Statuses;
    public function getNodeStatus(string $id): Status;
    public function setNodeStatus(string $id, string $status): void;
}