<?php

declare(strict_types=1);

namespace Opsminded\Graph;

interface RepoGraphInterface
{
    public function getGraph(): GraphModel;

    public function nodeExists(string $id): bool;
    public function addNode(NodeModel $node): bool;
    public function updateNode(NodeModel $node): bool;
    public function removeNode(string $id): bool;

    public function edgeExists(string $source, string $target): bool;
    public function addEdge(EdgeModel $edge): bool;
    public function removeEdge(string $source, string $target): bool;
}
