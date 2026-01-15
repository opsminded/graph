<?php

declare(strict_types=1);

final class HTTPController implements HTTPControllerInterface
{
    private ServiceInterface $service;
    private Logger $logger;

    public function __construct(ServiceInterface $service, Logger $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }

    public function getUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getUser');
        }
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $user = $this->service->getUser($id);
        if(is_null($user)) {
            return new HTTPNotFoundResponse('user not found', ['id' => $id]);
        }
        $data = $user->toArray();
        return new HTTPOKResponse('user found', $data);
    }

    public function insertUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'POST') {
            return new HTTPMethodNotAllowedResponse($req->method, 'insertUser');
        }
        if(! array_key_exists('id', $req->data)) {
            return new HTTPBadRequestResponse('key id not found in data', $req->data);
        }
        if(! array_key_exists('user_group', $req->data)) {
            return new HTTPBadRequestResponse('key user_group not found in data', $req->data);
        }
        $user = new ModelUser($req->data['id'], new ModelGroup($req->data['user_group']));
        $this->service->insertUser($user);
        $data = $user->toArray();
        return new HTTPCreatedResponse('user created', $data);
    }

    public function updateUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'PUT') {
            return new HTTPMethodNotAllowedResponse($req->method, 'updateUser');
        }
        $user = new ModelUser($req->data['id'], new ModelGroup($req->data['user_group']));
        if($this->service->updateUser($user)) {
            return new HTTPOKResponse('user updated', $req->data);
        }
        $data = $user->toArray();
        return new HTTPNotFoundResponse('user not found', $data);
    }

    public function getGraph(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getGraph');
        }
        $data = $this->service->getGraph()->toArray();
        return new HTTPOKResponse('get graph', $data);
    }

    public function getNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getNode');
        }
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $node = $this->service->getNode($id);
        if(is_null($node)) {
            return new HTTPNotFoundResponse('node not found', ['id' => $id]);
        }
        $data = $node->toArray();
        return new HTTPOKResponse('node found', $data);
    }

    public function getNodes(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getNodes');
        }
        $nodesArr = $this->service->getNodes();
        $data = [];
        foreach($nodesArr as $node) {
            $data[] = $node->toArray();
        }
        return new HTTPOKResponse('nodes found', $data);
    }

    public function getNodeParentOf(HTTPRequest $req): HTTPResponseInterface
    {
        if ($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getNodeParentOf');
        }
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $parent = $this->service->getNodeParentOf($id);
        if(is_null($parent)) {
            return new HTTPNotFoundResponse('parent node not found', ['id' => $id]);
        }
        $data = $parent->toArray();
        return new HTTPOKResponse('parent node found', $data);
    }

    public function getDependentNodesOf(HTTPRequest $req): HTTPResponseInterface
    {
        if ($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getDependentNodesOf');
        }
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $dependents = $this->service->getDependentNodesOf($id);
        $data = [];
        foreach($dependents as $dependent) {
            $data[] = $dependent->toArray();
        }
        return new HTTPOKResponse('dependent nodes found', $data);
    }

    public function insertNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'POST') {
            return new HTTPMethodNotAllowedResponse($req->method, 'insertNode');
        }
        $node = new ModelNode($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $req->data['data']);
        $this->service->insertNode($node);
        $this->logger->info('node inserted', $req->data);
        return new HTTPCreatedResponse('node inserted', $req->data);
    }
    
    public function updateNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'PUT') {
            return new HTTPMethodNotAllowedResponse($req->method, 'updateNode');
        }
        $this->logger->debug('updating node', $req->data);
        $node = new ModelNode($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $req->data['data']);
        $this->service->updateNode($node);
        $this->logger->info('node updated', $req->data);
        $resp = new HTTPCreatedResponse('node updated', $req->data);
        return $resp;
    }
    
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'DELETE') {
            return new HTTPMethodNotAllowedResponse($req->method, 'deleteNode');
        }
        $node = new ModelNode($req->data['id'], 'label', 'application', 'database', []);
        if($this->service->deleteNode($node)) {
            return new HTTPNoContentResponse('node deleted', ['id' => $req->data['id']]);
        }
        return new HTTPNotFoundResponse('node not found',['id' => $req->data['id']]);
    }

    public function getEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getEdge');
        }
        try {
            $source = $req->getParam('source');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        try {
            $target = $req->getParam('target');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $edge = $this->service->getEdge($source, $target);
        if(is_null($edge)) {
            return new HTTPNotFoundResponse('edge not found', ['source' => $source, 'target' => $target]);
        }
        $data = $edge->toArray();
        return new HTTPOKResponse('edge found', $data);
    }
    
    public function getEdges(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getEdges');
        }
        $edges = $this->service->getEdges();
        return new HTTPOKResponse('node found', []);
    }
    
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'POST') {
            return new HTTPMethodNotAllowedResponse($req->method, 'insertEdge');
        }
        $edge = new ModelEdge($req->data['source'], $req->data['target']);
        $this->service->insertEdge($edge);
        $data = $edge->toArray();
        return new HTTPOKResponse('node found', $data);
    }
    
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'PUT') {
            return new HTTPMethodNotAllowedResponse($req->method, 'updateEdge');
        }
        $edge = new ModelEdge($req->data['source'], $req->data['target'], $req->data['data']);
        $this->service->updateEdge($edge);
        $data = $edge->toArray();
        return new HTTPOKResponse('edge updated', $data);
    }
    
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'DELETE') {
            return new HTTPMethodNotAllowedResponse($req->method, 'deleteEdge');
        }
        $source = $req->data['source'];
        $target = $req->data['target'];
        $edge = new ModelEdge($source, $target, []);
        $this->service->deleteEdge($edge);
        $data = $req->data;
        return new HTTPNoContentResponse('edge deleted', $data);
    }

    public function getStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getStatus');
        }
        $statusData = $this->service->getStatus();
        $data = [];
        foreach($statusData as $status) {
            $data[] = $status->toArray();
        }
        return new HTTPOKResponse('nodes found', $data);
    }
    
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getNodeStatus');
        }
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $status = $this->service->getNodeStatus($id);
        if(!is_null($status)) {
            $data = $status->toArray();
            return new HTTPOKResponse('node found', $data);
        }
        return new HTTPNotFoundResponse('node not found', ['id' => $id]);
    }
    
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'PUT') {
            return new HTTPMethodNotAllowedResponse($req->method, 'updateNodeStatus');
        }
        $status = new ModelStatus($req->data['node_id'], $req->data['status']);
        $this->service->updateNodeStatus($status);
        $data = $status->toArray();
        return new HTTPOKResponse('node found', $data);
    }

    public function getLogs(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== 'GET') {
            return new HTTPMethodNotAllowedResponse($req->method, 'getLogs');
        }
        try {
            $limit = $req->getParam('limit');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $logsData = $this->service->getLogs(intval($limit));
        $data = [];
        foreach($logsData as $log) {
            $data[] = $log->toArray();
        }
        return new HTTPOKResponse('logs found', $data);
    }
}
