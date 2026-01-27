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

    public function getProject(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(ModelProject::PROJECT_KEYNAME_ID);
        } catch(HTTPRequestException $e) {
            return new HTTPBadRequestResponse($e->getMessage(), []);
        }
        $project = $this->service->getProject($id);
        
        if(!is_null($project)) {
            $data = $project->toArray();
            return new HTTPOKResponse("project found", $data);
        }
        return new HTTPNotFoundResponse("project not found", [ModelProject::PROJECT_KEYNAME_ID => $id]);
    }

    public function getProjects(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_GET) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }
        $projectsData = $this->service->getProjects();
        $data = [];
        foreach($projectsData as $project) {
            $data[] = $project->toArray();
        }
        return new HTTPOKResponse("projects found", $data);
    }

    public function insertProject(HTTPRequest $req): HTTPResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== HTTPRequest::METHOD_POST) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        if (! array_key_exists(ModelProject::PROJECT_KEYNAME_NODES, $req->data)) {
            return new HTTPBadRequestResponse("key " . ModelProject::PROJECT_KEYNAME_NODES . " not found in data", $req->data);
        }

        $now = new DateTimeImmutable();

        $id = $this->createSlug($req->data[ModelProject::PROJECT_KEYNAME_NAME] ?? 'project');

        $project = new ModelProject(
            $id,
            $req->data[ModelProject::PROJECT_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            $req->data[ModelProject::PROJECT_KEYNAME_NODES],
        );
        $this->service->insertProject($project);
        $data = $project->toArray();
        return new HTTPCreatedResponse("project created", $data);
    }

    public function updateProject(HTTPRequest $req): HTTPResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== HTTPRequest::METHOD_PUT) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $nodes = $req->data[ModelProject::PROJECT_KEYNAME_NODES];

        $now = new DateTimeImmutable();

        $project = new ModelProject(
            $req->data[ModelProject::PROJECT_KEYNAME_ID],
            $req->data[ModelProject::PROJECT_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            $nodes,
        );
        if($this->service->updateProject($project)) {
            $data = $project->toArray();
            return new HTTPOKResponse("project updated", $data);
        }
        return new HTTPNotFoundResponse("project not updated", [ModelProject::PROJECT_KEYNAME_ID => $req->data[ModelProject::PROJECT_KEYNAME_ID]]);
    }
    public function deleteProject(HTTPRequest $req): HTTPResponseInterface
    {
        if($req->method !== HTTPRequest::METHOD_DELETE) {
            return new HTTPMethodNotAllowedResponse($req->method, __METHOD__);
        }

        $project = new ModelProject(
            $req->data[ModelProject::PROJECT_KEYNAME_ID],
            '',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            [],
        );
        if($this->service->deleteProject($project->id)) {
            return new HTTPNoContentResponse("project deleted", [ModelProject::PROJECT_KEYNAME_ID => $req->data[ModelProject::PROJECT_KEYNAME_ID]]);
        }
        return new HTTPNotFoundResponse("project not deleted",[ModelProject::PROJECT_KEYNAME_ID => $req->data[ModelProject::PROJECT_KEYNAME_ID]]);
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

    private function createSlug(string $text): string
    {
        // Convert to lowercase
        $slug = mb_strtolower($text, 'UTF-8');
        
        // Replace common accented characters
        $unwanted = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $slug = strtr($slug, $unwanted);
        
        // Remove any remaining non-alphanumeric characters (except hyphens)
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        
        // Replace multiple hyphens with single hyphen
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens from start and end
        $slug = trim($slug, '-');
        
        // Add random 5-character suffix
        $randomSuffix = bin2hex(random_bytes(3)); // 3 bytes = 6 hex chars, take 5
        $randomSuffix = substr($randomSuffix, 0, 5);
        
        return $slug . '-' . $randomSuffix;
    }
}
