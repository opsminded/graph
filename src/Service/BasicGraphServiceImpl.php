<?php

declare(strict_types=1);

namespace Opsminded\Graph\Service;

use Opsminded\Graph\Repository\GraphRepoInterface;

final class BasicGraphServiceImpl implements GraphServiceInterface
{
    private GraphRepoInterface $repo;

    public function __construct(GraphRepoInterface $repo)
    {
        $this->repo = $repo;
    }

    public function getGraph(): array
    {
        $nodes = $this->repo->getNodes();
        $edges = $this->repo->getEdges();
        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }
}
