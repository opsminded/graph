<?php

declare(strict_types=1);

final class ProjectNode
{
    private string $projectId;
    private string $nodeId;

    public function __construct(string $projectId, string $nodeId)
    {
        $this->projectId = $projectId;
        $this->nodeId = $nodeId;
    }
    
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'node_id' => $this->nodeId,
        ];
    }
}