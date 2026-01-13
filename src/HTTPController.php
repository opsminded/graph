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
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $user = $this->service->getUser($id);
        if(is_null($user)) {
            return new HTTPNotFoundResponse('user not found', ['id' => $id]);
        }
        return new HTTPOKResponse('user found', $user->toArray());
    }

    public function insertUser(HTTPRequest $req): HTTPResponseInterface
    {
        $user = new ModelUser($req->data['id'], new ModelGroup($req->data['user_group']));
        $this->service->insertUser($user);
        return new HTTPCreatedResponse('user created', $req->data);
    }

    public function updateUser(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $user = new ModelUser($req->data['id'], new ModelGroup($req->data['user_group']));
            $this->service->updateUser($user);
            return new HTTPOKResponse('user updated', $req->data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getGraph(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $data = $this->service->getGraph()->toArray();
            return new HTTPOKResponse('get graph', $data);
        } catch (Exception $e) {
            throw $e;
        }

        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getNode(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $id = $req->getParam('id');
            $node = $this->service->getNode($id);
            if(is_null($node)) {
                return new HTTPNotFoundResponse('node not found', ['id' => $id]);
            }
            $data = $node->toArray();
            return new HTTPOKResponse('node found', $data);
        } catch( Exception $e)
        {
            throw $e;
        }

        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getNodes(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $nodes = $this->service->getNodes();
            // TODO: corrigir
            return new HTTPOKResponse('nodes found', []);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function insertNode(HTTPRequest $req): HTTPResponseInterface
    {
        $this->logger->debug('inserting node', $req->toArray());
        try {
            $node = new ModelNode($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $req->data['data']);
            $this->service->insertNode($node);
            $this->logger->info('node inserted', $req->data);
            return new HTTPCreatedResponse('node inserted', $req->data);
        } catch (Exception $e) {
            throw $e;
        }
        return new InternalServerErrorResponse('unknow error inserting node', $req->data);
    }
    
    public function updateNode(HTTPRequest $req): HTTPResponseInterface
    {
        $this->logger->debug('updating node', $req->data);
        
        try {
            $node = new ModelNode($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $req->data['data']);
            $this->service->updateNode($node);
            $this->logger->info('node updated', $req->data);
            $resp = new HTTPCreatedResponse('node updated', $req->data);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse('unknow todo in updateNode', $req->data);
    }
    
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface
    {
        $node = new ModelNode($req->data['id'], 'label', 'application', 'database', []);
        if($this->service->deleteNode($node)) {
            return new HTTPNoContentResponse('node deleted', ['id' => $req->data['id']]);
        }
        return new HTTPNotFoundResponse('node not found',['id' => $req->data['id']]);
    }

    public function getEdge(HTTPRequest $req): HTTPResponseInterface
    {
        $edge = $this->service->getEdge($req->data['source'], $req->data['target']);
        if(is_null($edge)) {
            return new HTTPNotFoundResponse('edge not found', $req->data);
        }
        $data = $edge->toArray();
        return new HTTPOKResponse('edge found', $data);
    }
    
    public function getEdges(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $edges = $this->service->getEdges();
            // TODO: corrigir
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface
    {
        $edge = new ModelEdge($req->data['source'], $req->data['target']);
        $this->service->insertEdge($edge);
        return new HTTPOKResponse('node found', []);
    }
    
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $edge = new ModelEdge($req->data['source'], $req->data['target'], $req->data['data']);
            $this->service->updateEdge($edge);
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface
    {
        $source = $req->data['source'];
        $target = $req->data['target'];
        $edge = new ModelEdge($source, $target, []);
        $this->service->deleteEdge($edge);
        return new HTTPNoContentResponse('edge deleted', $req->data);
    }

    public function getStatus(HTTPRequest $req): HTTPResponseInterface
    {
        $status = $this->service->getStatus();
        return new HTTPOKResponse('nodes found', []);
    }
    
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $id = $req->getParam('id');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }

        try {
            $status = $this->service->getNodeStatus($req->data['id']);
            $data = $status->toArray();
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        $status = new ModelStatus($req->data['node_id'], $req->data['status']);
        $this->service->updateNodeStatus($status);
        return new HTTPOKResponse('node found', []);
    }

    public function getLogs(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $limit = $req->getParam('limit');
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $logs = $this->service->getLogs(intval($limit));
            return new HTTPOKResponse('logs found', []);
    }
}
