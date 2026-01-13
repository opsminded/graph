<?php

declare(strict_types=1);

final class Service implements ServiceInterface
{
    private const SECURE_ACTIONS = [
        'Service::getUser'          => true,
        'Service::getGraph'         => true,
        'Service::getNode'          => true,
        'Service::getNodes'         => true,
        'Service::getEdge'          => true,
        'Service::getEdges'         => true,
        'Service::getStatus'        => true,
        'Service::getNodeStatus'    => true,
        'Service::updateNodeStatus' => true,
        'Service::getLogs'          => true,

        'Service::insertUser'       => false,
        'Service::updateUser'       => false,

        'Service::insertNode'       => false,
        'Service::updateNode'       => false,
        'Service::deleteNode'       => false,
        'Service::insertEdge'       => false,
        'Service::updateEdge'       => false,
        'Service::deleteEdge'       => false,
        'Service::insertLog'        => false,
    ];

    private DatabaseInterface $database;
    private Logger $logger;

    public function __construct(DatabaseInterface $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    public function getUser(string $id): ?ModelUser
    {
        $this->logger->debug('getting user', ['id' => $id]);
        $this->verify();
        $data = $this->database->getUser($id);
        if (! is_null($data)) {
            $g = new ModelGroup($data['user_group']);
            $user = new ModelUser($id, $g);
            $this->logger->debug('user found', ['id' => $id, 'user' => $data]);
            return $user;
        }
        $this->logger->debug('user not found', ['id' => $id]);
        return null;
    }

    public function insertUser(ModelUser $user): bool
    {
        $this->logger->debug('inserting user', ['user' => $user->toArray()]);
        $this->verify();
        $this->database->insertUser($user->getId(), $user->getGroup()->getId());
        $this->logger->debug('user inserted', ['user' => $user->toArray()]);
        return true;
    }

    public function updateUser(ModelUser $user): bool
    {
        $this->logger->debug('updating user', ['user' => $user->toArray()]);
        $this->verify();
        if ($this->database->updateUser($user->getId(), $user->getGroup()->getId())) {
            $this->logger->debug('user updated', ['user' => $user->toArray()]);
            return true;
        }
        return false;
    }

    public function getGraph(): ModelGraph
    {
        $this->logger->debug('getting graph');
        $this->verify();
        $nodes = $this->getNodes();
        $edges = $this->getEdges();
        $graph = new ModelGraph($nodes, $edges);
        $this->logger->debug('returning graph', $graph->toArray());
        return $graph;
    }

    public function getNode(string $id): ?ModelNode
    {
        $this->logger->debug('getting node');
        $this->verify();
        $data = $this->database->getNode($id);
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
        $this->logger->debug('getting nodes');
        $this->verify();
        $nodesData = $this->database->getNodes();
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

    public function insertNode(ModelNode $node): bool
    {
        $this->logger->debug('inserting node', $node->toArray());
        $this->verify();
        $this->logger->debug('permission allowed', $node->toArray());
        $this->insertLog(new ModelLog('node', $node->getId(), 'insert', null, $node->toArray()));
        if ($this->database->insertNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData())) {
            $this->logger->info('node inserted', $node->toArray());
            return true;
        }
        throw new RuntimeException('unexpected error on Service::insertNode');
    }

    public function updateNode(ModelNode $node): bool
    {
        $this->logger->debug('updating node', ['node' => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error('node not found', $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog(new ModelLog('node', $node->getId(), 'update', $old->toArray(), $node->toArray()));
        if ($this->database->updateNode($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData())) {
            $this->logger->info('node updated', $node->toArray());
            return true;
        }
        throw new RuntimeException('unexpected error on Service::updateNode');
    }

    public function deleteNode(ModelNode $node): bool
    {
        $this->logger->debug('deleting node', ['node' => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error('node not found', $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog(new ModelLog( 'node', $node->getId(), 'delete', $old->toArray(), null));
        if ($this->database->deleteNode($node->getId())) {
            $this->logger->info('node deleted', $node->toArray());
            return true;
        }
        throw new RuntimeException('unexpected error on Service::deleteNode');
    }

    public function getEdge(string $source, string $target): ?ModelEdge
    {
        $this->logger->debug('getting edge', ['source' => $source, 'target' => $target]);
        $this->verify();
        $edgeData = $this->database->getEdge($source, $target);
        if (! is_null($edgeData)) {
            $edge = new ModelEdge($edgeData['source'], $edgeData['target'], $edgeData['data']);
            $data = $edge->toArray();
            $this->logger->info('edge found', $data);
            return $edge;
        }
        $this->logger->info('edge not found', ['source' => $source, 'target' => $target]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug('getting edges');
        $this->verify();

        $edgesData = $this->database->getEdges();
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

    public function insertEdge(ModelEdge $edge): bool
    {
        $this->logger->debug('inserting edge', ['edge' => $edge->toArray()]);
        $this->verify();
        $this->insertLog(new ModelLog( 'edge', $edge->getId(), 'insert', null, $edge->toArray()));
        if ($this->database->insertEdge($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getData())) {
            $this->logger->info('edge inserted', ['edge' => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException('unexpected error on Service::insertEdge');
    }

    public function updateEdge(ModelEdge $edge): bool
    {
        $this->logger->debug('updating edge', ['edge' => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getSource(), $edge->getTarget());
        if (is_null($exists)) {
            $this->logger->error('edge not found', ['edge' => $edge->toArray()]);
            return false;
        }

        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog(new ModelLog('edge', $edge->getId(), 'update', $old->toArray(), $edge->toArray()));
        if ($this->database->updateEdge($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getData())) {
            $this->logger->info('edge updated', ['edge' => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException('unexpected error on Service::updateEdge');
    }

    public function deleteEdge(ModelEdge $edge): bool
    {
        $this->logger->debug('deleting edge', ['edge' => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getSource(), $edge->getTarget());
        if (is_null($exists)) {
            $this->logger->error('edge not found', ['edge' => $edge->toArray()]);
            return false;
        }
        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog(new ModelLog('edge', $edge->getId(), 'delete', $old->toArray(), null));
        if ($this->database->deleteEdge($edge->getId())) {
            $this->logger->info('edge deleted', ['edge' => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException('unexpected error on Service::deleteEdge');
    }

    public function getStatus(): array
    {
        $this->logger->debug('getting status');
        $this->verify();
        $statusesData = $this->database->getStatus();
        $nodeStatuses = [];
        foreach ($statusesData as $status) {
            $status = new ModelStatus($status['id'], $status['status'] ?? 'unknown');
            $nodeStatuses[] = $status;
        }
        $this->logger->info('status found', ['status' => $nodeStatuses]);
        return $nodeStatuses;
    }

    public function getNodeStatus(string $id): ?ModelStatus
    {
        $this->logger->debug('getting node status', ['id' => $id]);
        $this->verify();
        $statusData = $this->database->getNodeStatus($id);
        if (! is_null($statusData)) {
            $this->logger->info('status found', $statusData);
            return new ModelStatus($id, $statusData['status'] ?? 'unknown');
        }
        $this->logger->error('status not found', ['id' => $id]);
        return null;
    }

    public function updateNodeStatus(ModelStatus $status): bool
    {
        $this->logger->debug('updating node status', ['status' => $status->toArray()]);
        $this->verify();
        $data = $status->toArray();
        if ($this->database->updateNodeStatus($status->getNodeId(), $status->getStatus())) {
            $this->logger->info('node status updated', $data);
            return true;
        }
        throw new RuntimeException('unexpected error on Service::updateNodeStatus');
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug('getting logs', ['limit' => $limit]);
        $this->verify();
        $logs = [];
        $rows = $this->database->getLogs($limit);
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

    private function insertLog(ModelLog $log): void
    {
        $this->logger->debug('insert log', ['log' => $log]);
        $user_id   = HelperContext::getUser();
        $ip_address = HelperContext::getClientIP();
        $this->database->insertLog($log->entityType, $log->entityId, $log->action, $log->oldData, $log->newData, $user_id, $ip_address);
    }

    private function verify(): void
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";
        $group  = HelperContext::getGroup();

        $this->logger->debug('verify', ['action' => $action, 'group' => $group]);

        // if is admin, allow all
        if ($group === 'admin') {
            $this->logger->info('allow admin', ['action' => $action, 'group' => $group]);
            return;
        }

        // if action is in the SAFE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            $this->logger->info('allow safe action', ['action' => $action, 'group' => $group]);
            return;
        }
        
        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] == false && $group == 'contributor')
        {
            $this->logger->info('contributor is allowed', ['action' => $action, 'group' => $group]);
            return;
        }

        $this->logger->info('not authorized', ['action' => $action, 'group' => $group]);
        throw new RuntimeException('action not allowed: ' . $action);
    }
}