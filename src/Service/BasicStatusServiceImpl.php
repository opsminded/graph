<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Model\Status;
use Opsminded\Graph\Model\Statuses;
use Opsminded\Graph\Repository\StatusRepoInterface;

final class BasicStatusServiceImpl implements StatusServiceInterface
{
    private StatusRepoInterface $repo;

    public function __construct(StatusRepoInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getStatuses(): Statuses
    {
        $rows = $this->repo->getStatuses();
        $statuses = new Statuses();
        foreach ($rows as $row) {
            $statuses->addStatus(new Status($row['node_id'], $row['status']));
        }
        return $statuses;
    }

    public function getNodeStatus(string $id): Status
    {
        $status = $this->repo->getNodeStatus($id);
        return new Status($id, $status);
    }

    public function setNodeStatus(string $id, string $status): void
    {
        $this->repo->setNodeStatus($id, $status);
    }
}