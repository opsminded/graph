<?php

declare(strict_types=1);

namespace Opsminded\Graph\Repository;

use Opsminded\Graph\Logger;

final class LoggerRepoImpl implements GraphRepoInterface
{
    private Logger $logger;
    private GraphRepoInterface $repo;

    public function __construct(GraphRepoInterface $repo, Logger $logger)
    {
        $this->repo   = $repo;
        $this->logger = $logger;
    }

    public function getNode(string $id): ?array
    {
        $this->logger->debug("Getting node with ID: $id");
        return $this->repo->getNode($id);
    }

    public function getNodes(): array
    {
        $this->logger->debug("Getting all nodes");
        return $this->repo->getNodes();
    }

    public function getNodeExists(string $id): bool
    {
        $this->logger->debug("Checking if node with ID: $id exists");
        return $this->repo->getNodeExists($id);
    }

    public function insertNode(string $id, array $data): bool
    {
        $this->logger->debug("Inserting node with ID: $id");
        return $this->repo->insertNode($id, $data);
    }

    public function updateNode(string $id, array $data): bool
    {
        $this->logger->debug("Updating node with ID: $id");
        return $this->repo->updateNode($id, $data);
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug("Deleting node with ID: $id");
        return $this->repo->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?array
    {
        $this->logger->debug("Getting edge from source: $source to target: $target");
        return $this->repo->getEdge($source, $target);
    }

    public function getEdges(): array
    {
        $this->logger->debug("Getting all edges");
        return $this->repo->getEdges();
    }

    public function getEdgeExists(string $source, string $target): bool
    {
        $this->logger->debug("Checking if edge exists from source: $source to target: $target");
        return $this->repo->getEdgeExists($source, $target);
    }

    public function insertEdge(string $source, string $target, array $data = []): bool
    {
        $this->logger->debug("Inserting edge from source: $source to target: $target");
        return $this->repo->insertEdge($source, $target, $data);
    }

    public function updateEdge(string $source, string $target, array $data = []): bool
    {
        $this->logger->debug("Updating edge from source: $source to target: $target");
        return $this->repo->updateEdge($source, $target, $data);
    }

    public function deleteEdge(string $source, string $target): bool
    {
        $this->logger->debug("Deleting edge from source: $source to target: $target");
        return $this->repo->deleteEdge($source, $target);
    }
}
