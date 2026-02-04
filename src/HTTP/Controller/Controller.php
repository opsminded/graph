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
            $id = $req->getParam('id');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $user = $this->service->getUser($id);
        if(is_null($user)) {
            return new NotFoundResponse("user not found", ['id' => $id]);
        }
        $data = $user->toArray();
        return new OKResponse("user found", $data);
    }

    public function insertUser(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_POST) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        if(! array_key_exists('id', $req->data)) {
            return new BadRequestResponse("key id not found in data", $req->data);
        }
        if(! array_key_exists('group', $req->data)) {
            return new BadRequestResponse("key group not found in data", $req->data);
        }
        $user = new User($req->data['id'], new Group($req->data['group']));
        $this->service->insertUser($user);
        $data = $user->toArray();
        return new CreatedResponse("user created", $data);
    }

    public function updateUser(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $user = new User($req->data['id'], new Group($req->data['group']));
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

    public function getCategoryTypes(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $category = $req->getParam('category');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $types = $this->service->getCategoryTypes($category);
        $data = [];
        foreach($types as $type) {
            $data[] = $type->toArray();
        }
        return new OKResponse("types found", $data);
    }

    public function getTypeNodes(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $type = $req->getParam('type');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $nodesArr = $this->service->getTypeNodes($type);
        $data = [];
        foreach($nodesArr as $node) {
            $data[] = $node->toArray();
        }
        return new OKResponse("nodes found", $data);
    }

    public function getNode(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam('id');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $node = $this->service->getNode($id);
        if(is_null($node)) {
            return new NotFoundResponse("node not found", ['id' => $id]);
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
            $req->data['id'], 
            $req->data['label'], 
            $req->data['category'], 
            $req->data['type'], 
            $req->data['data']
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
            $req->data['id'],
            $req->data['label'],
            $req->data['category'],
            $req->data['type'],
            $req->data['data']
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
        $id = $req->data['id'];
        if($this->service->deleteNode($id)) {
            return new NoContentResponse("node deleted", ['id' => $req->data['id']]);
        }
        return new NotFoundResponse("node not found",['id' => $req->data['id']]);
    }

    public function getEdge(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $source = $req->getParam('source');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        try {
            $target = $req->getParam('target');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $edge = $this->service->getEdge($source, $target);
        if(is_null($edge)) {
            return new NotFoundResponse("edge not found", ['source' => $source, 'target' => $target]);
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
            $req->data['source'],
            $req->data['target'],
            $req->data['label'],
            $req->data['data'],
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
            $req->data['source'], 
            $req->data['target'], 
            $req->data['label'],
            $req->data['data'],
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
        
        $source = $req->data['source'];
        $target = $req->data['target'];

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
    
    public function updateNodeStatus(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_PUT) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        $status = new Status($req->data['node_id'], $req->data['status']);
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
            $id = $req->getParam('id');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $project = $this->service->getProject($id);
        
        $this->logger->debug("getting project", ['id' => $id, "project" => $project]);
        
        if(!is_null($project)) {
            $data = $project->toArray();
            return new OKResponse("project found", $data);
        }
        return new NotFoundResponse("project not found", ['id' => $id]);
    }

    public function getProjectGraph(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        try {
            $id = $req->getParam('id');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }

        $projectGraph = $this->service->getProjectGraph($id);
        if(is_null($projectGraph)) {
            return new NotFoundResponse("project graph not found", ['id' => $id]);
        }

        $graph = $this->cytoscapeHelper->convertToCytoscapeFormat($projectGraph);
        return new OKResponse("project graph found", $graph);
    }

    public function getProjectStatus(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_GET) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }
        try {
            $id = $req->getParam('id');
        } catch(RequestException $e) {
            return new BadRequestResponse($e->getMessage(), []);
        }
        $projectStatus = $this->service->getProjectStatus($id);
        if(is_null($projectStatus)) {
            return new NotFoundResponse("project status not found", ['id' => $id]);
        }
        
        $status = [];

        foreach($projectStatus as $s) {
            $status[] = ['node_id' => $s->getNodeId(), 'status' => $s->getStatus()];
        }

        return new OKResponse("project status found", $status);
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

        $id = $this->createSlug($req->data['name']);

        $project = new Project(
            $id,
            $req->data['name'],
            $creator,
            $now,
            $now,
            [],
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
            $req->data['id'],
            $req->data['name'],
            $creator,
            $now,
            $now,
            $req->data['data'],
        );
        if($this->service->updateProject($project)) {
            $data = $project->toArray();
            return new OKResponse("project updated", $data);
        }
        return new NotFoundResponse("project not updated", ['id' => $req->data['id']]);
    }
    public function deleteProject(Request $req): ResponseInterface
    {
        if($req->method !== Request::METHOD_DELETE) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $project = new Project(
            $req->data['id'],
            '',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            [],
        );
        if($this->service->deleteProject($project->getId())) {
            return new NoContentResponse("project deleted", ['id' => $req->data['id']]);
        }
        return new NotFoundResponse("project not deleted",['id' => $req->data['id']]);
    }

    public function insertProjectNode(Request $req): ResponseInterface
    {
        if ($req->method !== Request::METHOD_POST) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $node = new ProjectNode(
            $req->data['project_id'],
            $req->data['node_id']
        );

        if ($this->service->insertProjectNode($node)) {
            return new CreatedResponse("project node inserted", $req->data);
        }
        return new BadRequestResponse("project node not inserted", $req->data);
    }

    public function deleteProjectNode(Request $req): ResponseInterface
    {
        if ($req->method !== Request::METHOD_DELETE) {
            return new MethodNotAllowedResponse($req->method, __METHOD__);
        }

        $projectId = $req->data['project_id'];
        $nodeId = $req->data['node_id'];

        if ($this->service->deleteProjectNode($projectId, $nodeId)) {
            return new NoContentResponse("project node deleted", $req->data);
        }
        return new NotFoundResponse("project node not found", $req->data);
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
