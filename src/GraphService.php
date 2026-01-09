<?php

declare(strict_types=1);

final class GraphService implements GraphServiceInterface
{
    private const SECURE_ACTIONS = [
        'GraphService::getUser'          => true,
        'GraphService::getGraph'         => true,
        'GraphService::getNode'          => true,
        'GraphService::getNodes'         => true,
        'GraphService::getEdge'          => true,
        'GraphService::getEdges'         => true,
        'GraphService::getStatus'        => true,
        'GraphService::getNodeStatus'    => true,
        'GraphService::updateNodeStatus' => true,
        'GraphService::getLogs'          => true,

        'GraphService::insertUser'       => false,
        'GraphService::updateUser'       => false,

        'GraphService::insertNode'       => false,
        'GraphService::updateNode'       => false,
        'GraphService::deleteNode'       => false,
        'GraphService::insertEdge'       => false,
        'GraphService::updateEdge'       => false,
        'GraphService::deleteEdge'       => false,
        'GraphService::insertLog'        => false,
    ];

    private GraphDatabaseInterface $db;
    private Logger $logger;

    public function __construct(GraphDatabaseInterface $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getUser(string $id): ?ModelUser
    {
        $this->verify();
        $data = $this->db->getUser($id);
        if (! is_null($data)) {
            $g = new ModelGroup($data['user_group']);
            $user = new ModelUser($id, $g);
            return $user;
        }
        return null;
    }

    public function insertUser(ModelUser $user): void
    {
        $this->verify();
        $this->db->insertUser($user->getId(), $user->getGroup()->getId());
    }

    public function updateUser(ModelUser $user): void
    {
        $this->verify();
        $this->db->updateUser($user->getId(), $user->getGroup()->getId());
    }

    public function getGraph(): ModelGraph
    {
        $this->verify();
        $nodes = $this->getNodes();
        $edges = $this->getEdges();
        $graph = new ModelGraph($nodes, $edges);
        return $graph;
    }

    public function getNode(string $id): ?ModelNode
    {
        $this->verify();
        $data = $this->db->getNode($id);
        if (! is_null($data)) {
            return new ModelNode(
                $data['id'],
                $data['label'],
                $data['category'],
                $data['type'],
                $data['data']
            );
        }
        return null;
    }

    public function getNodes(): array
    {
        $this->verify();
        $nodesData = $this->db->getNodes();
        $nodes     = [];
        foreach ($nodesData as $data) {
            $node = new ModelNode(
                $data['id'],
                $data['label'],
                $data['category'],
                $data['type'],
                $data['data']
            );
            $nodes[] = $node;
        }
        return $nodes;
    }

    public function insertNode(ModelNode $node): void
    {
        $this->logger->debug('inserting node', $node->toArray());

        $this->verify();
        $this->logger->debug('permission allowed', $node->toArray());
        $this->insertLog(new ModelLog('node', $node->getId(), 'insert', null, $node->toArray()));
        $this->db->insertNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData());
        $this->logger->info('node inserted', $node->toArray());
    }

    public function updateNode(ModelNode $node): void
    {
        $this->verify();

        $exists = $this->db->getNode($node->getId());
        if(is_null($exists)) {
            return;
        }

        $old = $this->getNode($node->getId());
        $this->insertLog(new ModelLog('node', $node->getId(), 'update', $old->toArray(), $node->toArray()));
        $this->db->updateNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData());
    }

    public function deleteNode(string $id): void
    {
        $this->verify();

        $exists = $this->db->getNode($id);
        if(is_null($exists)) {
            return;
        }

        $old = $this->getNode($id);
        $this->insertLog(new ModelLog( 'node', $id, 'delete', $old->toArray(), null));
        $this->db->deleteNode($id);
    }

    public function getEdge(string $source, string $target): ?ModelEdge
    {
        $this->verify();
        $data = $this->db->getEdge($source, $target);
        if(! is_null($data)) {
            return new ModelEdge(
                $data['source'],
                $data['target'],
                $data['data']
            );
        }
        return null;
    }

    public function getEdges(): array
    {
        $this->verify();

        $edgesData = $this->db->getEdges();
        $edges     = [];
        foreach ($edgesData as $data) {
            $edge = new ModelEdge(
                $data['source'],
                $data['target'],
                $data['data']
            );
            $edges[] = $edge;
        }
        return $edges;
    }

    public function insertEdge(ModelEdge $edge): void
    {
        $this->verify();
        $this->insertLog(new ModelLog( 'edge', $edge->getId(), 'insert', null, $edge->toArray()));
        $this->db->insertEdge($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getData());
    }

    public function updateEdge(ModelEdge $edge): void
    {
        $this->verify();
        $exists = $this->db->getEdge($edge->getSource(), $edge->getTarget());
        if (is_null($exists)) {
            return;
        }

        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog(new ModelLog('edge', $edge->getId(), 'update', $old->toArray(), $edge->toArray()));
        $this->db->updateEdge($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getData());
    }

    public function deleteEdge(ModelEdge $edge): void
    {
        $this->verify();
        $this->db->deleteEdge($edge->getId());
    }

    public function getStatus(): array
    {
        $this->verify();

        $statusesData = $this->db->getStatus();
        $nodeStatuses = [];
        foreach ($statusesData as $data) {
            $status = new ModelStatus($data['id'], $data['status'] ?? 'unknown');
            $nodeStatuses[] = $status;
        }
        return $nodeStatuses;
    }

    public function getNodeStatus(string $id): ModelStatus
    {
        $this->verify();
        $statusData = $this->db->getNodeStatus($id);
        return new ModelStatus($id, $statusData['status'] ?? 'unknown');
    }

    public function updateNodeStatus(ModelStatus $status): void
    {
        $this->verify();
        $this->db->updateNodeStatus($status->getNodeId(), $status->getStatus());
    }

    public function getLogs($limit): array
    {
        $this->verify();
        $logs = [];
        $rows = $this->db->getLogs($limit);
        foreach ($rows as $row) {
            $old_data = $row['old_data'] ? json_decode($row['old_data'], true) : [];
            $new_data = $row['new_data'] ?  json_decode($row['new_data'], true) : [];
            $log = new ModelLog(
                $row['entity_type'],
                $row['entity_id'],
                $row['action'],
                $old_data,
                $new_data,
            );
            $log->userId    = $row['user_id'];
            $log->ipAddress = $row['ip_address'];
            $log->createdAt = $row['created_at'];
            $logs[] = $log;
        }
        return $logs;
    }

    private function insertLog(ModelLog $auditLog): void
    {
        $user_id   = GraphContext::getUser();
        $ip_address = GraphContext::getClientIp();

        $this->db->insertLog(
            $auditLog->entityType,
            $auditLog->entityId,
            $auditLog->action,
            $auditLog->oldData,
            $auditLog->newData,
            $user_id,
            $ip_address
        );
    }

    private function verify(): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";

        $group = GraphContext::getGroup();

        // if is admin, allow all
        if ($group === 'admin') {
            return;
        }

        // if action is in the SAFE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            return;
        }
        
        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] == false && $group == 'contributor')
        {
            return;
        }

        throw new RuntimeException('action not allowed: ' . $action);
    }
}