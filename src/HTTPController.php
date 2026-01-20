<?php

declare(strict_types=1);

final class HTTPController implements HTTPControllerInterface
{
    private ServiceInterface $service;
    private HelperCytoscape $cytoscapeHelper;
    private Logger $logger;

    public function __construct(ServiceInterface $service, HelperCytoscape $cytoscapeHelper, Logger $logger)
    {
        $this->service = $service;
        $this->cytoscapeHelper = $cytoscapeHelper;
        $this->logger = $logger;
    }

    public function getUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelUser::USER_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $user = $this->service->getUser($id);
        if(is_null($user)) {
            return new HTTPNotFoundResponse("user not found", [ModelUser::USER_KEYNAME_ID => $id]);
        }
        $data = $user->toArray();
        return new HTTPOKResponse("user found", $data);
    }

    public function insertUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_POST) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        if(! array_key_exists(ModelUser::USER_KEYNAME_ID, $req->data)) {
            return new HTTPBadRequestResponse("key " . ModelUser::USER_KEYNAME_ID . " not found in data", $req->data);
        }
        if(! array_key_exists(ModelUser::USER_KEYNAME_GROUP, $req->data)) {
            return new HTTPBadRequestResponse("key " . ModelUser::USER_KEYNAME_GROUP . " not found in data", $req->data);
        }
        $user = new ModelUser($req->data[ModelUser::USER_KEYNAME_ID], new ModelGroup($req->data[ModelUser::USER_KEYNAME_GROUP]));
        $this->service->insertUser($user);
        $data = $user->toArray();
        return new HTTPCreatedResponse("user created", $data);
    }

    public function updateUser(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $user = new ModelUser($req->data[ModelUser::USER_KEYNAME_ID], new ModelGroup($req->data[ModelUser::USER_KEYNAME_GROUP]));
        if($this->service->updateUser($user)) {
            return new HTTPOKResponse("user updated", $req->data);
        }
        $data = $user->toArray();
        return new HTTPNotFoundResponse("user not found", $data);
    }

    public function getCategories(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $categories = $this->service->getCategories();
        $data = [];
        foreach($categories as $category) {
            $data[] = $category->toArray();
        }
        return new HTTPOKResponse("categories found", $data);
    }

    public function getTypes(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $types = $this->service->getTypes();
        $data = [];
        foreach($types as $type) {
            $data[] = $type->toArray();
        }
        return new HTTPOKResponse("types found", $data);
    }

    public function getCytoscapeGraph(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $g = $this->service->getGraph();
        $data = $this->cytoscapeHelper->toArray($g);
        return new HTTPOKResponse("get graph", $data);
    }

    public function getNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelNode::NODE_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $node = $this->service->getNode($id);
        if(is_null($node)) {
            return new HTTPNotFoundResponse("node not found", [ModelNode::NODE_KEYNAME_ID => $id]);
        }
        $data = $node->toArray();
        return new HTTPOKResponse("node found", $data);
    }

    public function getNodes(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $nodesArr = $this->service->getNodes();
        $data = [];
        foreach($nodesArr as $node) {
            $data[] = $node->toArray();
        }
        return new HTTPOKResponse("nodes found", $data);
    }

    public function getNodeParentOf(HTTPRequest $req): HTTPResponseInterface
    {
        if ($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelNode::NODE_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $parent = $this->service->getNodeParentOf($id);
        if(is_null($parent)) {
            return new HTTPNotFoundResponse("parent node not found", [ModelNode::NODE_KEYNAME_ID => $id]);
        }
        $data = $parent->toArray();
        return new HTTPOKResponse("parent node found", $data);
    }

    public function getDependentNodesOf(HTTPRequest $req): HTTPResponseInterface
    {
        if ($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelNode::NODE_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $dependents = $this->service->getDependentNodesOf($id);
        $data = [];
        foreach($dependents as $dependent) {
            $data[] = $dependent->toArray();
        }
        return new HTTPOKResponse("dependent nodes found", $data);
    }

    public function insertNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_POST) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $node = new ModelNode(
            $req->data[ModelNode::NODE_KEYNAME_ID], 
            $req->data[ModelNode::NODE_KEYNAME_LABEL], 
            $req->data[ModelNode::NODE_KEYNAME_CATEGORY], 
            $req->data[ModelNode::NODE_KEYNAME_TYPE], 
            $req->data[ModelNode::NODE_KEYNAME_USERCREATED],
            $req->data[ModelNode::NODE_KEYNAME_DATA]
        );
        $this->service->insertNode($node);
        $this->logger->info("node inserted", $req->data);
        return new HTTPCreatedResponse("node inserted", $req->data);
    }
    
    public function updateNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $this->logger->debug("updating node", $req->data);
        $node = new ModelNode(
            $req->data[ModelNode::NODE_KEYNAME_ID],
            $req->data[ModelNode::NODE_KEYNAME_LABEL],
            $req->data[ModelNode::NODE_KEYNAME_CATEGORY],
            $req->data[ModelNode::NODE_KEYNAME_TYPE],
            false,
            $req->data[ModelNode::NODE_KEYNAME_DATA]
        );
        $this->service->updateNode($node);
        $this->logger->info("node updated", $req->data);
        $resp = new HTTPCreatedResponse("node updated", $req->data);
        return $resp;
    }
    
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_DELETE) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $node = new ModelNode($req->data[ModelNode::NODE_KEYNAME_ID], "label", "application", "database", false, []);
        if($this->service->deleteNode($node)) {
            return new HTTPNoContentResponse("node deleted", [ModelNode::NODE_KEYNAME_ID => $req->data[ModelNode::NODE_KEYNAME_ID]]);
        }
        return new HTTPNotFoundResponse("node not found",[ModelNode::NODE_KEYNAME_ID => $req->data[ModelNode::NODE_KEYNAME_ID]]);
    }

    public function getEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $source = $req->getParam(ModelEdge::EDGE_KEYNAME_SOURCE);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        try {
            $target = $req->getParam(ModelEdge::EDGE_KEYNAME_TARGET);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $edge = $this->service->getEdge($source, $target);
        if(is_null($edge)) {
            return new HTTPNotFoundResponse("edge not found", [ModelEdge::EDGE_KEYNAME_SOURCE => $source, ModelEdge::EDGE_KEYNAME_TARGET => $target]);
        }
        $data = $edge->toArray();
        return new HTTPOKResponse("edge found", $data);
    }
    
    public function getEdges(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edges = $this->service->getEdges();
        $data = [];
        foreach($edges as $edge) {
            $data[] = $edge->toArray();
        }
        return new HTTPOKResponse("edges found", $data);
    }
    
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_POST) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edge = new ModelEdge(
            $req->data[ModelEdge::EDGE_KEYNAME_SOURCE],
            $req->data[ModelEdge::EDGE_KEYNAME_TARGET],
            $req->data[ModelEdge::EDGE_KEYNAME_LABEL],
            $req->data[ModelEdge::EDGE_KEYNAME_DATA],
        );

        $this->service->insertEdge($edge);
        $data = $edge->toArray();
        return new HTTPOKResponse("node found", $data);
    }
    
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edge = new ModelEdge(
            $req->data[ModelEdge::EDGE_KEYNAME_SOURCE], 
            $req->data[ModelEdge::EDGE_KEYNAME_TARGET], 
            $req->data[ModelEdge::EDGE_KEYNAME_LABEL],
            $req->data[ModelEdge::EDGE_KEYNAME_DATA],
        );
        
        $this->service->updateEdge($edge);
        $data = $edge->toArray();
        return new HTTPOKResponse("edge updated", $data);
    }
    
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_DELETE) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        
        $edge = new ModelEdge(
            $req->data[ModelEdge::EDGE_KEYNAME_SOURCE],
            $req->data[ModelEdge::EDGE_KEYNAME_TARGET],
            '',
            [],
        );

        $this->service->deleteEdge($edge);
        $data = $req->data;
        return new HTTPNoContentResponse("edge deleted", $data);
    }

    public function getStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $statusData = $this->service->getStatus();
        $data = [];
        foreach($statusData as $status) {
            $data[] = $status->toArray();
        }
        return new HTTPOKResponse("nodes found", $data);
    }
    
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelStatus::STATUS_KEYNAME_NODE_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $status = $this->service->getNodeStatus($id);
        if(!is_null($status)) {
            $data = $status->toArray();
            return new HTTPOKResponse("node found", $data);
        }
        return new HTTPNotFoundResponse("node not found", [ModelStatus::STATUS_KEYNAME_NODE_ID => $id]);
    }
    
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $status = new ModelStatus($req->data[ModelStatus::STATUS_KEYNAME_NODE_ID], $req->data[ModelStatus::STATUS_KEYNAME_STATUS]);
        $this->service->updateNodeStatus($status);
        $data = $status->toArray();
        return new HTTPOKResponse("node found", $data);
    }

    public function getSave(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelSave::SAVE_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $save = $this->service->getSave($id);
        if(!is_null($save)) {
            $data = $save->toArray();
            return new HTTPOKResponse("save found", $data);
        }
        return new HTTPNotFoundResponse("save not found", [ModelSave::SAVE_KEYNAME_ID => $id]);
    }

    public function getSaves(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $savesData = $this->service->getSaves();
        $data = [];
        foreach($savesData as $save) {
            $data[] = $save->toArray();
        }
        return new HTTPOKResponse("saves found", $data);
    }

    public function insertSave(HTTPRequest $req): HTTPResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== HTTPRequest::METHOD_POST) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        if (! array_key_exists(ModelSave::SAVE_KEYNAME_NODES, $req->data)) {
            return new HTTPBadRequestResponse("key " . ModelSave::SAVE_KEYNAME_NODES . " not found in data", $req->data);
        }

        $now = new DateTimeImmutable();

        $save = new ModelSave(
            $req->data[ModelSave::SAVE_KEYNAME_ID],
            $req->data[ModelSave::SAVE_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            $req->data[ModelSave::SAVE_KEYNAME_NODES],
        );
        $this->service->insertSave($save);
        $data = $save->toArray();
        return new HTTPCreatedResponse("save created", $data);
    }

    public function updateSave(HTTPRequest $req): HTTPResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $nodes = $req->data[ModelSave::SAVE_KEYNAME_NODES];

        $now = new DateTimeImmutable();

        $save = new ModelSave(
            $req->data[ModelSave::SAVE_KEYNAME_ID],
            $req->data[ModelSave::SAVE_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            $nodes,
        );
        if($this->service->updateSave($save)) {
            $data = $save->toArray();
            return new HTTPOKResponse("save updated", $data);
        }
        return new HTTPNotFoundResponse("save not updated", [ModelSave::SAVE_KEYNAME_ID => $req->data[ModelSave::SAVE_KEYNAME_ID]]);
    }
    public function deleteSave(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_DELETE) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $save = new ModelSave(
            $req->data[ModelSave::SAVE_KEYNAME_ID],
            '',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            [],
        );
        if($this->service->deleteSave($save->id)) {
            return new HTTPNoContentResponse("save deleted", [ModelSave::SAVE_KEYNAME_ID => $req->data[ModelSave::SAVE_KEYNAME_ID]]);
        }
        return new HTTPNotFoundResponse("save not deleted",[ModelSave::SAVE_KEYNAME_ID => $req->data[ModelSave::SAVE_KEYNAME_ID]]);
    }

    public function getLogs(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $limit = $req->getParam(DatabaseInterface::DATABASE_KEYWORD_LIMIT);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $logsData = $this->service->getLogs(intval($limit));
        $data = [];
        foreach($logsData as $log) {
            $data[] = $log->toArray();
        }
        return new HTTPOKResponse("logs found", $data);
    }
}
