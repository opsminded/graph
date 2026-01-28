<?php

declare(strict_types=1);

final class Controller implements ControllerInterface
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

    public function getUser(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(User::USER_KEYNAME_ID);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $user = $this->service->getUser($id);
        if(is_null($user)) {
            return new NotFoundResponse("user not found", [User::USER_KEYNAME_ID => $id]);
        }
        $data = $user->toArray();
        return new OKResponse("user found", $data);
    }

    public function insertUser(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_POST) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        if(! array_key_exists(User::USER_KEYNAME_ID, $req->data)) {
            return new BadRequestResponse("key " . User::USER_KEYNAME_ID . " not found in data", $req->data);
        }
        if(! array_key_exists(User::USER_KEYNAME_GROUP, $req->data)) {
            return new BadRequestResponse("key " . User::USER_KEYNAME_GROUP . " not found in data", $req->data);
        }
        $user = new User($req->data[User::USER_KEYNAME_ID], new Group($req->data[User::USER_KEYNAME_GROUP]));
        $this->service->insertUser($user);
        $data = $user->toArray();
        return new CreatedResponse("user created", $data);
    }

    public function updateUser(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $user = new User($req->data[User::USER_KEYNAME_ID], new Group($req->data[User::USER_KEYNAME_GROUP]));
        if($this->service->updateUser($user)) {
            return new OKResponse("user updated", $req->data);
        }
        $data = $user->toArray();
        return new NotFoundResponse("user not found", $data);
    }

    public function getCategories(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $categories = $this->service->getCategories();
        $data = [];
        foreach($categories as $category) {
            $data[] = $category->toArray();
        }
        return new OKResponse("categories found", $data);
    }

    public function getTypes(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $types = $this->service->getTypes();
        $data = [];
        foreach($types as $type) {
            $data[] = $type->toArray();
        }
        return new OKResponse("types found", $data);
    }

    public function getNode(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(Node::NODE_KEYNAME_ID);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $node = $this->service->getNode($id);
        if(is_null($node)) {
            return new NotFoundResponse("node not found", [Node::NODE_KEYNAME_ID => $id]);
        }
        $data = $node->toArray();
        return new OKResponse("node found", $data);
    }

    public function getNodes(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $nodesArr = $this->service->getNodes();
        $data = [];
        foreach($nodesArr as $node) {
            $data[] = $node->toArray();
        }
        return new OKResponse("nodes found", $data);
    }

    public function insertNode(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_POST) {
            return new  MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $node = new Node(
            $req->data[Node::NODE_KEYNAME_ID], 
            $req->data[Node::NODE_KEYNAME_LABEL], 
            $req->data[Node::NODE_KEYNAME_CATEGORY], 
            $req->data[Node::NODE_KEYNAME_TYPE], 
            $req->data[Node::NODE_KEYNAME_USERCREATED],
            $req->data[Node::NODE_KEYNAME_DATA]
        );
        $this->service->insertNode($node);
        $this->logger->info("node inserted", $req->data);
        return new CreatedResponse("node inserted", $req->data);
    }
    
    public function updateNode(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $this->logger->debug("updating node", $req->data);
        $node = new Node(
            $req->data[Node::NODE_KEYNAME_ID],
            $req->data[Node::NODE_KEYNAME_LABEL],
            $req->data[Node::NODE_KEYNAME_CATEGORY],
            $req->data[Node::NODE_KEYNAME_TYPE],
            false,
            $req->data[Node::NODE_KEYNAME_DATA]
        );
        $this->service->updateNode($node);
        $this->logger->info("node updated", $req->data);
        $resp = new CreatedResponse("node updated", $req->data);
        return $resp;
    }
    
    public function deleteNode(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_DELETE) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $id = $req->data[Node::NODE_KEYNAME_ID];
        if($this->service->deleteNode($id)) {
            return new NoContentResponse("node deleted", [Node::NODE_KEYNAME_ID => $req->data[Node::NODE_KEYNAME_ID]]);
        }
        return new NotFoundResponse("node not found",[Node::NODE_KEYNAME_ID => $req->data[Node::NODE_KEYNAME_ID]]);
    }

    public function getEdge(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $source = $req->getParam(Edge::EDGE_KEYNAME_SOURCE);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        try {
            $target = $req->getParam(Edge::EDGE_KEYNAME_TARGET);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $edge = $this->service->getEdge($source, $target);
        if(is_null($edge)) {
            return new NotFoundResponse("edge not found", [Edge::EDGE_KEYNAME_SOURCE => $source, Edge::EDGE_KEYNAME_TARGET => $target]);
        }
        $data = $edge->toArray();
        return new OKResponse("edge found", $data);
    }
    
    public function getEdges(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edges = $this->service->getEdges();
        $data = [];
        foreach($edges as $edge) {
            $data[] = $edge->toArray();
        }
        return new OKResponse("edges found", $data);
    }
    
    public function insertEdge(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_POST) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edge = new Edge(
            $req->data[Edge::EDGE_KEYNAME_SOURCE],
            $req->data[Edge::EDGE_KEYNAME_TARGET],
            $req->data[Edge::EDGE_KEYNAME_LABEL],
            $req->data[Edge::EDGE_KEYNAME_DATA],
        );

        $this->service->insertEdge($edge);
        $data = $edge->toArray();
        return new OKResponse("node found", $data);
    }
    
    public function updateEdge(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $edge = new Edge(
            $req->data[Edge::EDGE_KEYNAME_SOURCE], 
            $req->data[Edge::EDGE_KEYNAME_TARGET], 
            $req->data[Edge::EDGE_KEYNAME_LABEL],
            $req->data[Edge::EDGE_KEYNAME_DATA],
        );
        
        $this->service->updateEdge($edge);
        $data = $edge->toArray();
        return new OKResponse("edge updated", $data);
    }
    
    public function deleteEdge(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_DELETE) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        
        $source = $req->data[Edge::EDGE_KEYNAME_SOURCE];
        $target = $req->data[Edge::EDGE_KEYNAME_TARGET];

        $this->service->deleteEdge($source, $target);
        $data = $req->data;
        return new NoContentResponse("edge deleted", $data);
    }

    // public function getStatus(Request $req): ResponseInterface
    // {
    //     if($req->method !== Request::METHOD_GET) {
    //         return new MethodNotAllowedResponse($req->method, __METHOD__);
    //     }
    //     $statusData = $this->service->getStatus();
    //     $data = [];
    //     foreach($statusData as $status) {
    //         $data[] = $status->toArray();
    //     }
    //     return new OKResponse("nodes found", $data);
    // }
    
    public function getNodeStatus(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(Status::STATUS_KEYNAME_NODE_ID);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $status = $this->service->getNodeStatus($id);
        if(!is_null($status)) {
            $data = $status->toArray();
            return new OKResponse("node found", $data);
        }
        return new NotFoundResponse("node not found", [Status::STATUS_KEYNAME_NODE_ID => $id]);
    }
    
    public function updateNodeStatus(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $status = new Status($req->data[Status::STATUS_KEYNAME_NODE_ID], $req->data[Status::STATUS_KEYNAME_STATUS]);
        $this->service->updateNodeStatus($status);
        $data = $status->toArray();
        return new OKResponse("node found", $data);
    }

    public function getProject(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam(Project::PROJECT_KEYNAME_ID);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $project = $this->service->getProject($id);
        
        if(!is_null($project)) {
            $data = $project->toArray();
            return new OKResponse("project found", $data);
        }
        return new NotFoundResponse("project not found", [Project::PROJECT_KEYNAME_ID => $id]);
    }

    public function getProjects(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $projectsData = $this->service->getProjects();
        $data = [];
        foreach($projectsData as $project) {
            $data[] = $project->toArray();
        }
        return new OKResponse("projects found", $data);
    }

    public function insertProject(Request $req): ResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== Request::METHOD_POST) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $now = new DateTimeImmutable();

        $id = $this->createSlug($req->data[Project::PROJECT_KEYNAME_NAME] ?? 'project');

        $project = new Project(
            $id,
            $req->data[Project::PROJECT_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            null,
            $req->data['data'],
        );
        $this->service->insertProject($project);
        $data = $project->toArray();
        return new CreatedResponse("project created", $data);
    }

    public function updateProject(Request $req): ResponseInterface
    {
        $creator = HelperContext::getUser();

        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $now = new DateTimeImmutable();

        $project = new Project(
            $req->data[Project::PROJECT_KEYNAME_ID],
            $req->data[Project::PROJECT_KEYNAME_NAME],
            $creator,
            $now,
            $now,
            null,
            $req->data['data'],
        );
        if($this->service->updateProject($project)) {
            $data = $project->toArray();
            return new OKResponse("project updated", $data);
        }
        return new NotFoundResponse("project not updated", [Project::PROJECT_KEYNAME_ID => $req->data[Project::PROJECT_KEYNAME_ID]]);
    }
    public function deleteProject(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_DELETE) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $project = new Project(
            $req->data[Project::PROJECT_KEYNAME_ID],
            '',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            null,
            [],
        );
        if($this->service->deleteProject($project->getId())) {
            return new NoContentResponse("project deleted", [Project::PROJECT_KEYNAME_ID => $req->data[Project::PROJECT_KEYNAME_ID]]);
        }
        return new NotFoundResponse("project not deleted",[Project::PROJECT_KEYNAME_ID => $req->data[Project::PROJECT_KEYNAME_ID]]);
    }

    public function getLogs(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $limit = $req->getParam(DatabaseInterface::DATABASE_KEYWORD_LIMIT);
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $logsData = $this->service->getLogs(intval($limit));
        $data = [];
        foreach($logsData as $log) {
            $data[] = $log->toArray();
        }
        return new OKResponse("logs found", $data);
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
