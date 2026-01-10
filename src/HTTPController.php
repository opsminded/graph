<?php

declare(strict_types=1);


final class HTTPController implements HTTPControllerInterface
{
    private GraphServiceInterface $service;
    private Logger $logger;

    public function __construct(GraphServiceInterface $service, Logger $logger)
    {
        $this->service = $service;
        $this->logger = $logger;
    }

    public function getUser(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $id = $req->getParam('id');
            $user = $this->service->getUser($id);
            if(is_null($user)) {
                return new HTTPNotFoundResponse('User not found', ['id' => $id]);
            }
            return new HTTPOKResponse('user found', $user->toArray());
        } catch(HTTPRequestException $e) {
            return new BadRequestResponse('bad request: ' . $e->getMessage(), $req->data);
        } catch(GraphServiceException $e) {
            return new InternalServerErrorResponse('user not created: ' . $e->getMessage(), $req->data);
        }
        throw new GraphControllerException('other internal error in getUser');
    }

    public function insertUser(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $user = new User($req->data['id'], new Group($req->data['user_group']));
            $this->service->insertUser($user);
            return new CreatedResponse('user created', $req->data);
        } catch(GraphServiceException $e) {
            throw $e;
        }
        
        return new HTTPOKResponse('user created', $req->data);
    }

    public function updateUser(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $user = new User($req->data['id'], new Group($req->data['user_group']));
            $this->service->updateUser($user);
            return new HTTPOKResponse('user updated', $req->data);
        } catch(GraphServiceException $e) {
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
                return new NotFoundResponse('node not found', ['id' => $id]);
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
            $data = json_decode($req->data['data'], true);
            $node = new Node($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $data);
            $this->service->insertNode($node);
            $this->logger->info('node inserted', $req->data);
            return new CreatedResponse('node inserted', $req->data);
        } catch( GraphServiceException $e)
        {
            throw $e;
        }
        return new InternalServerErrorResponse('unknow error inserting node', $req->data);
    }
    
    public function updateNode(HTTPRequest $req): HTTPResponseInterface
    {
        $this->logger->debug('updating node', $req->data);

        try {
            $data = json_decode($req->data['data'], true);
            $node = new Node($req->data['id'], $req->data['label'], $req->data['category'], $req->data['type'], $data);
            $this->service->updateNode($node);
            $this->logger->info('node updated', $req->data);

            $req->data['data'] = $data;
            $resp = new CreatedResponse('node updated', $req->data);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse('unknow todo in updateNode', $req->data);
    }
    
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $this->service->deleteEdge($req->data['id']);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse('unknow todo in deleteNode', $req->data);
    }

    public function getEdge(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $edge = $this->service->getEdge($req->data['source'], $req->data['target']);
            $data = $edge->toArray();
            return new HTTPOKResponse('node found', $data);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
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
        try {
            $edge = new Edge(null, $req->data['source'], $req->data['target']);
            $this->service->insertEdge($edge);
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $edge = new Edge($req->data['id'], $req->data['source'], $req->data['target'], $req->data['data']);
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
        try {
            $id = $req->data['id'];
            $this->service->deleteEdge($id);
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getStatuses(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $statuses = $this->service->getStatuses();
            return new HTTPOKResponse('node found', []);
            return $resp;
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
    
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
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
        try {
            $status = new NodeStatus($req->data['node_id'], $req->data['status']);
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }

    public function getLogs(HTTPRequest $req): HTTPResponseInterface
    {
        try {
            $logs = $this->service->getLogs($req->getParam('limit'));
            return new HTTPOKResponse('node found', []);
        } catch( Exception $e)
        {
            throw $e;
        }
        
        return new InternalServerErrorResponse($e->getMessage(), $req->data);
    }
}
