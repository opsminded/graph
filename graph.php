<?php

declare(strict_types=1);

class HTTPResponse implements HTTPResponseInterface
{
    public int $code;
    public string $status;
    public string $message;
    public array $data;

    public function __construct(int $code, string $status, string $message = '', array $data)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->code);
        $this->data = ['code' => $this->code, 'status' => $this->status, 'message' => $this->message, 'data' => $this->data];
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
    }
}
#####################################

class HTTPInternalServerErrorResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(500, 'error', $message, $data);
    }
}
#####################################

final class ModelGraph
{
    private array $nodes = [];
    private array $edges = [];

    public function __construct(array $nodes, array $edges)
    {
        $this->nodes = $nodes;
        $this->edges = $edges;
    }
    
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getEdges(): array
    {
        return $this->edges;
    }

    public function toArray(): array
    {
        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
        ];
    }
}
#####################################

final class ModelNode
{
    public const ID_VALIDATION_REGEX = '/^[a-zA-Z0-9\-_]+$/';
    public const LABEL_MAX_LENGTH    = 120;
    
    private string $id;
    private string $label;
    private string $categoryID;
    private string $typeID;

    private array $data = [];

    public function __construct(string $id, string $label, string $categoryID, string $typeID, array $data)
    {
        $this->validate($id, $label);
        $this->id         = $id;
        $this->label      = $label;
        $this->categoryID = $categoryID;
        $this->typeID     = $typeID;
        $this->data       = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCategory(): string
    {
        return $this->categoryID;
    }

    public function getType(): string
    {
        return $this->typeID;
    }

    public function getData(): array
    {
        return $this->data;
    }

    private function validate(string $id, string $label): void
    {
        if (!preg_match(self::ID_VALIDATION_REGEX, $id)) {
            throw new InvalidArgumentException("Invalid node ID: {$id}");
        }

        if (strlen($label) > self::LABEL_MAX_LENGTH) {
            throw new InvalidArgumentException("Node label exceeds maximum length of " . self::LABEL_MAX_LENGTH);
        }
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'label'    => $this->label,
            'category' => $this->categoryID,
            'type'     => $this->typeID,
            'data'     => $this->data
        ];
    }
}
#####################################

class HTTPNoContentResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(204, 'success', $message, $data);
    }
}
#####################################

final class ModelGroup
{
    private const ALLOWED_GROUPS = ['anonymous', 'consumer', 'contributor', 'admin'];
    
    private string $id;
    
    public function __construct(string $id)
    {
        if (!in_array($id, self::ALLOWED_GROUPS, true)) {
            throw new InvalidArgumentException("Invalid user group: {$id}");
        }
        $this->id  = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id
        ];
    }
}
#####################################

final class ModelType
{
    public string $id;
    public string $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
#####################################

class HTTPUnauthorizedResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(401, 'error', $message, $data);
    }
}
#####################################

class HTTPBadRequestResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(400, 'error', $message, $data);
    }
}
#####################################

interface HTTPResponseInterface
{
    public function send(): void;
}

#####################################

interface LoggerInterface
{
    public function info(string $message, array $data = []): void;
    public function debug(string $message, array $data = []): void;
    public function error(string $message, array $data = []): void;
}
#####################################

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

#####################################

final class Service implements ServiceInterface
{
    private const SECURE_ACTIONS = [
        'Service::getUser'             => true,
        'Service::getCategories'       => true,
        'Service::getTypes'            => true,
        'Service::getGraph'            => true,
        'Service::getNode'             => true,
        'Service::getNodes'            => true,
        'Service::getNodeParentOf'     => true,
        'Service::getDependentNodesOf' => true,
        'Service::getEdge'             => true,
        'Service::getEdges'            => true,
        'Service::getStatus'           => true,
        'Service::getNodeStatus'       => true,
        'Service::updateNodeStatus'    => true,
        'Service::getLogs'             => true,
        'Service::insertUser'          => false,
        'Service::updateUser'          => false,
        'Service::insertCategory'      => false,
        'Service::insertType'          => false,
        'Service::insertNode'          => false,
        'Service::updateNode'          => false,
        'Service::deleteNode'          => false,
        'Service::insertEdge'          => false,
        'Service::updateEdge'          => false,
        'Service::deleteEdge'          => false,
        'Service::insertLog'           => false,
    ];

    private const ADMIN_ACTIONS = [
        'Service::insertUser',
        'Service::updateUser',
        'Service::insertCategory',
        'Service::insertType',
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

    public function getCategories(): array
    {
        $this->logger->debug('getting categories');
        $this->verify();
        $categoriesData = $this->database->getCategories();
        $categories     = [];
        foreach ($categoriesData as $data) {
            $category = new ModelCategory(
                $data['id'],
                $data['name'],
                $data['shape'],
                $data['width'],
                $data['height'],
            );
            $categories[] = $category;
        }
        return $categories;
    }

    public function insertCategory(ModelCategory $category): bool
    {
        $this->logger->debug('inserting category', $category->toArray());
        $this->verify();
        if ($this->database->insertCategory($category->id, $category->name, $category->shape, $category->width, $category->height)) {
            $this->logger->info('category inserted', $category->toArray());
            return true;
        }
        throw new RuntimeException('unexpected error on Service::insertCategory');
    }
    
    public function getTypes(): array
    {
        $this->logger->debug('getting types');
        $this->verify();
        $typesData = $this->database->getTypes();
        $types     = [];
        foreach ($typesData as $data) {
            $type = new ModelType(
                $data['id'],
                $data['name'],
            );
            $types[] = $type;
        }
        return $types;
    }
    public function insertType(ModelType $type): bool
    {
        $this->logger->debug('inserting type', $type->toArray());
        $this->verify();
        if ($this->database->insertType($type->id, $type->name)) {
            $this->logger->info('type inserted', $type->toArray());
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

    public function getNodeParentOf(string $id): ?ModelNode
    {
        $this->logger->debug('getting parent node of', ['id' => $id]);
        $this->verify();
        $parentData = $this->database->getNodeParentOf($id);
        if (! is_null($parentData)) {
            $parentNode = new ModelNode(
                $parentData['id'],
                $parentData['label'],
                $parentData['category'],
                $parentData['type'],
                $parentData['data']
            );
            $this->logger->info('parent node found', $parentNode->toArray());
            return $parentNode;
        }
        $this->logger->info('parent node not found', ['id' => $id]);
        return null;
    }
    public function getDependentNodesOf(string $id): array
    {
        $this->logger->debug('getting dependent nodes of', ['id' => $id]);
        $this->verify();
        $dependentNodesData = $this->database->getDependentNodesOf($id);
        $dependentNodes     = [];
        foreach ($dependentNodesData as $data) {
            $node = new ModelNode(
                $data['id'],
                $data['label'],
                $data['category'],
                $data['type'],
                $data['data']
            );
            $dependentNodes[] = $node;
        }
        $this->logger->info('dependent nodes found', ['count' => count($dependentNodes)]);
        return $dependentNodes;
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
            $oldData = $row['old_data'] ? json_decode($row['old_data'], true) : [];
            $newData = $row['new_data'] ? json_decode($row['new_data'], true) : [];
            $log = new ModelLog(
                $row['entity_type'],
                $row['entity_id'],
                $row['action'],
                $oldData,
                $newData,
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
        $userId   = HelperContext::getUser();
        $ipAddress = HelperContext::getClientIP();
        $this->database->insertLog($log->entityType, $log->entityId, $log->action, $log->oldData, $log->newData, $userId, $ipAddress);
    }

    private function verify(): void
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";
        $group  = HelperContext::getGroup();

        $this->logger->debug('verify', ['action' => $action, 'group' => $group]);

        if (! array_key_exists($action, self::SECURE_ACTIONS)) {
            $this->logger->error('action not found in SECURE_ACTIONS', ['action' => $action]);
            throw new RuntimeException('action not found in SECURE_ACTIONS: ' . $action);
        }

        if (in_array($action, self::ADMIN_ACTIONS, true) && $group !== 'admin') {
            $this->logger->info('only admin allowed', ['action' => $action, 'group' => $group]);
            throw new RuntimeException('action only allowed for admin: ' . $action);
        }

        // if action is in the SECURE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            $this->logger->info('allow safe action', ['action' => $action, 'group' => $group]);
            return;
        }

        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] === false && in_array($group, ['admin', 'contributor'], true)) {
            $this->logger->info('contributor and admin are allowed', ['action' => $action, 'group' => $group]);
            return;
        }

        $this->logger->info('not authorized', ['action' => $action, 'group' => $group]);
        throw new RuntimeException('action not allowed: ' . $action);
    }
}
#####################################

final class ModelLog
{
    public string $entityType;
    public string $entityId;
    public string $action;
    public ?array $oldData;
    public ?array $newData;
    public string $userId;
    public string $ipAddress;
    public string $createdAt;

    public function __construct(string $entityType, string $entityId, string $action, ?array $oldData = null, ?array $newData = null)
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->action     = $action;
        $this->oldData    = $oldData;
        $this->newData    = $newData;
    }

    public function toArray(): array
    {
        return [
            'entityType' => $this->entityType,
            'entityId'   => $this->entityId,
            'action'     => $this->action,
            'oldData'    => $this->oldData,
            'newData'    => $this->newData,
            'userId'     => $this->userId,
            'ipAddress'  => $this->ipAddress,
            'createdAt'  => $this->createdAt,
        ];
    }
}
#####################################

final class Database implements DatabaseInterface
{
    private PDO $pdo;
    private LoggerInterface $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
        $this->initSchema();
    }

    public function getUser(string $id): ?array
    {
        $this->logger->debug('getting user id', ['id' => $id]);
        $sql = 'SELECT * FROM users WHERE id = :id';
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info('user found', ['params' => $params, 'row' => $row]);
            return $row;
        }
        $this->logger->error('user not found', ['params' => $params]);
        return null;
    }

    public function insertUser(string $id, string $group): bool
    {
        $this->logger->debug('inserting new user', ['id' => $id, 'group' => $group]);
        $sql = 'INSERT INTO users (id, user_group) VALUES (:id, :group)';
        $params = [':id' => $id, ':group' => $group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    public function updateUser(string $id, string $group): bool
    {
        $this->logger->debug('updating new user', ['id' => $id, 'group' => $group]);
        $sql = 'UPDATE users SET user_group = :group WHERE id = :id';
        $params = [':id' => $id, ':group' => $group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->info('user updated', ['params' => $params]);
            return true;
        }
        $this->logger->info('user not updated', ['params' => $params]);
        return false;
    }

    public function getCategories(): array
    {
        $this->logger->debug('fetching categories');
        $sql = 'SELECT * FROM categories';
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        $this->logger->info('categories fetched', ['rows' => $rows]);
        return $rows;
    }

    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool
    {
        $this->logger->debug('inserting new category', ['id' => $id, 'name' => $name]);
        $sql = 'INSERT INTO categories (id, name, shape, width, height) VALUES (:id, :name, :shape, :width, :height)';
        $params = [':id' => $id, ':name' => $name, ':shape' => $shape, ':width' => $width, ':height' => $height];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('category inserted', ['params' => $params]);
        return true;
    }
    
    public function getTypes(): array
    {
        $this->logger->debug('fetching types');
        $sql = 'SELECT * FROM types';
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        $this->logger->info('types fetched', ['rows' => $rows]);
        return $rows;
    }

    public function insertType(string $id, string $name): bool
    {
        $this->logger->debug('inserting new type', ['id' => $id, 'name' => $name]);
        $sql = 'INSERT INTO types (id, name) VALUES (:id, :name)';
        $params = [':id' => $id, ':name' => $name];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('type inserted', ['params' => $params]);
        return true;
    }

    public function getNode(string $id): ?array
    {
        $this->logger->debug('fetching node', ['id' => $id]);
        $sql = 'SELECT * FROM nodes WHERE id = :id';
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info('node fetched', ['params' => $params, 'row' => $row]);
            return $row;
        }
        $this->logger->error('node not found', ['params' => $params]);
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug('fetching nodes');
        $sql = 'SELECT * FROM nodes';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info('nodes fetched', ['rows' => $rows]);
        return $rows;
    }

    public function getNodeParentOf(string $id): ?array
    {
        $this->logger->debug('fetching parent node');
        $sql = 'SELECT n.* FROM nodes n INNER JOIN edges e ON n.id = e.source WHERE e.target = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info('parent node fetched', ['row' => $row]);
            return $row;
        }
        $this->logger->error('parent node not found', ['id' => $id]);
        return null;
    }

    public function getDependentNodesOf(string $id): array
    {
        $this->logger->debug('fetching dependent nodes');
        $sql = 'SELECT n.* FROM nodes n INNER JOIN edges e ON n.id = e.target WHERE e.source = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info('dependent nodes fetched', ['rows' => $rows]);
        return $rows;
    }

    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        $this->logger->debug('inserting new node', ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'data' => $data]);
        $sql = 'INSERT INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)';
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':label' => $label, ':category' => $category, ':type' => $type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('node inserted', ['params' => $params]);
        return true;
    }

    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool
    {
        $this->logger->debug('updating node', ['id' => $id, 'label' => $label, 'category' => $category, 'type' => $type, 'data' => $data]);
        $sql = 'UPDATE nodes SET label = :label, category = :category, type = :type, data = :data WHERE id = :id';
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':label' => $label, ':category' => $category, ':type' => $type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info('node updated', ['params' => $params]);
            return true;
        }
        $this->logger->error('node not updated', ['params' => $params]);
        return false;
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug('deleting node', ['id' => $id]);
        $sql = 'DELETE FROM nodes WHERE id = :id';
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->debug('node deleted', ['params' => $params]);
            return true;
        }
        $this->logger->error('node not deleted', ['params' => $params]);
        return false;
    }

    public function getEdge(string $source, string $target): ?array
    {
        $this->logger->debug('getting edge', ['source' => $source, 'target' => $target]);
        $sql = 'SELECT * FROM edges WHERE source = :source AND target = :target';
        $params = [':source' => $source, ':target' => $target];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info('edge found', ['params' => $params, 'row' => $row]);
            return $row;
        }
        $this->logger->error('edge not found', ['params' => $params]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug('fetching edges');
        $sql = 'SELECT * FROM edges';
        $stmt  = $this->pdo->query($sql);
        $rows  = $stmt->fetchAll();
        foreach($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info('edges fetched', ['rows' => $rows]);
        return $rows;
    }

    public function insertEdge(string $id, string $source, string $target, array $data = []): bool
    {
        $this->logger->debug('inserting edge', ['id' => $id, 'source' => $source, 'target' => $target, 'data' => $data]);
        $edgeData = $this->getEdge($target, $source);
        if (! is_null($edgeData)) {
            $this->logger->error('cicle detected', $edgeData);
            return false;
        }
        $sql = 'INSERT INTO edges(id, source, target, data) VALUES (:id, :source, :target, :data)';
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':source' => $source, ':target' => $target, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('edge inserted', ['params' => $params]);
        return true;
    }

    public function updateEdge(string $id, string $source, string $target, array $data = []): bool
    {
        $this->logger->debug('updating edge', ['id' => $id, 'source' => $source, 'target' => $target, 'data' => $data]);
        $sql = 'UPDATE edges SET source = :source, target = :target, data = :data WHERE id = :id';
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $id, ':source' => $source, ':target' => $target, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if($stmt->rowCount() > 0) {
            $this->logger->info('edge updated', ['params' => $params]);
            return true;
        }
        $this->logger->error('edge not updated', ['params' => $params]);
        return false;
    }

    public function deleteEdge(string $id): bool
    {
        $this->logger->debug('deleting edge', ['id' => $id]);
        $sql = 'DELETE FROM edges WHERE id = :id';
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info('edge deleted', ['params' => $params]);
            return true;
        }
        $this->logger->error('edge not deleted', ['params' => $params]);
        return false;
    }

    public function getStatus(): array
    {
        $this->logger->debug('fetching statuses');
        $sql = 'SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id';
        $stmt   = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info('statuses fetched', ['rows' => $rows]);
        return $rows;
    }

    public function getNodeStatus(string $id): ?array
    {
        $this->logger->debug('fetching node status', ['id' => $id]);
        $sql = 'SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id WHERE n.id = :id';
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if($row) {
            $this->logger->info('node status fetched', ['params' => $params, 'row' => $row]);
            return $row;
        }
        return null;
    }

    public function updateNodeStatus(string $id, string $status): bool
    {
        $this->logger->debug('updating node status', ['id' => $id, 'status' => $status]);
        $sql = 'REPLACE INTO status (node_id, status) VALUES (:node_id, :status)';
        $params = [':node_id' => $id, ':status' => $status];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('node status updated', ['params' => $params]);
        return true;
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug('fetching logs', ['limit' => $limit]);
        $sql = 'SELECT * FROM audit ORDER BY created_at DESC LIMIT :limit';
        $params = [':limit' => $limit];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info('logs fetched', ['params' => $params, 'rows' => $rows]);
        return $rows;
    }

    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool
    {
        $this->logger->debug('inserting audit log', ['entity_type' => $entity_type, 'entity_id' => $entity_id, 'action' => $action, 'old_data' => $old_data, 'new_data' => $new_data, 'user_id' => $user_id, 'ip_address' => $ip_address]);
        $sql = 'INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address) VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)';
        $old_data = $old_data !== null ? json_encode($old_data, JSON_UNESCAPED_UNICODE) : null;
        $new_data = $new_data !== null ? json_encode($new_data, JSON_UNESCAPED_UNICODE) : null;
        $params = [':entity_type' => $entity_type, ':entity_id' => $entity_id, ':action' => $action, ':old_data' => $old_data, ':new_data' => $new_data, ':user_id' => $user_id, ':ip_address' => $ip_address];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info('audit log inserted', ['params' => $params]);
        return true;
    }

    private function initSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                user_group TEXT NOT NULL
            )');

        $this->pdo->exec('INSERT OR IGNORE INTO users VALUES(\'admin\', \'admin\')');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS categories (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                shape TEXT NOT NULL,
                width INTEGER NOT NULL,
                height INTEGER NOT NULL
            )');
        
        $this->pdo->exec("INSERT OR IGNORE INTO categories VALUES
            ('business', 'Business', 'box', 100, 50),
            ('application', 'Application', 'ellipse', 80, 80),
            ('network', 'Network', 'diamond', 90, 60),
            ('infrastructure', 'Infrastructure', 'hexagon', 110, 70)");
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS types (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL
            )');

        $this->pdo->exec("INSERT OR IGNORE INTO types VALUES
            ('server', 'Server'),
            ('service', 'Service'),
            ('database', 'Database')");

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS nodes (
                id TEXT PRIMARY KEY,
                label TEXT NOT NULL,
                category TEXT NOT NULL,
                type TEXT NOT NULL,
                data TEXT NOT NULL,
                FOREIGN KEY (category) REFERENCES categories(id),
                FOREIGN KEY (type) REFERENCES types(id)
            )');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS edges (
                id TEXT PRIMARY KEY,
                source TEXT NOT NULL,
                target TEXT NOT NULL,
                data TEXT,
                FOREIGN KEY (source) REFERENCES nodes(id) ON DELETE CASCADE,
                FOREIGN KEY (target) REFERENCES nodes(id) ON DELETE CASCADE
            )');

        $this->pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_edges_source_target ON edges (source, target)');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS status (
                node_id TEXT PRIMARY KEY NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (node_id) REFERENCES nodes(id) ON DELETE CASCADE
            )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_node_status_node_id ON status (node_id)');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS audit (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL,
                action TEXT NOT NULL,
                old_data TEXT,
                new_data TEXT,
                user_id TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )');
    }

    public static function createConnection(string $dsn): PDO
    {
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }
}
#####################################

final class Logger implements LoggerInterface
{
    public function info(string $message, array $data = []): void
    {
        $this->log('INFO', $message, $data);
        
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log('DEBUG', $message, $data);
    }

    public function error(string $message, array $data = []): void
    {
        $this->log('ERROR', $message, $data);
    }

    private function log(string $type, $message, $data = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$type}] {$method}: $message ($data)\n";
        error_log($message);
    }
}
#####################################

class HTTPMethodNotAllowedResponse extends HTTPResponse
{
    public function __construct(string $method, string $classMethod)
    {
        return parent::__construct(405, 'error', "method '{$method}' not allowed in '{$classMethod}'", ['method' => $method, 'location' => $classMethod]);
    }
}
#####################################

interface ServiceInterface
{
    public function getUser(string $id): ?ModelUser;
    public function insertUser(ModelUser $user): bool;
    public function updateUser(ModelUser $user): bool;

    public function getCategories(): array;
    public function insertCategory(ModelCategory $category): bool;
    
    public function getTypes(): array;
    public function insertType(ModelType $type): bool;

    public function getGraph(): ModelGraph;

    public function getNode(string $id): ?ModelNode;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?ModelNode;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(ModelNode $node): bool;
    public function updateNode(ModelNode $node): bool;
    public function deleteNode(ModelNode $node): bool;

    public function getEdge(string $source, string $target): ?ModelEdge;
    public function getEdges(): array;
    public function insertEdge(ModelEdge $edge): bool;
    public function updateEdge(ModelEdge $edge): bool;
    public function deleteEdge(ModelEdge $edge): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?ModelStatus;
    public function updateNodeStatus(ModelStatus $status): bool;

    public function getLogs(int $limit): array;
}
#####################################

interface HTTPControllerInterface
{
    public function getUser(HTTPRequest $req): HTTPResponseInterface;
    public function insertUser(HTTPRequest $req): HTTPResponseInterface;
    public function updateUser(HTTPRequest $req): HTTPResponseInterface;

    public function getGraph(HTTPRequest $req): HTTPResponseInterface;

    public function getNode(HTTPRequest $req): HTTPResponseInterface;
    public function getNodes(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeParentOf(HTTPRequest $req): HTTPResponseInterface;
    public function getDependentNodesOf(HTTPRequest $req): HTTPResponseInterface;
    public function insertNode(HTTPRequest $req): HTTPResponseInterface;
    public function updateNode(HTTPRequest $req): HTTPResponseInterface;
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface;

    public function getEdge(HTTPRequest $req): HTTPResponseInterface;
    public function getEdges(HTTPRequest $req): HTTPResponseInterface;
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface;
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface;
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface;

    public function getStatus(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface;
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface;

    public function getLogs(HTTPRequest $req): HTTPResponseInterface;
}
#####################################

final class HTTPRequestException extends RuntimeException
{
    private array $data;
    private array $params;
    private string $path;
    
    public function __construct($message, array $data, array $params, string $path)
    {
        parent::__construct($message, 0, null);
        $this->data = $data;
        $this->params = $params;
        $this->path = $path;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
#####################################

interface DatabaseInterface
{
    public function getUser(string $id): ?array;
    public function insertUser(string $id, string $group): bool;
    public function updateUser(string $id, string $group): bool;

    public function getCategories(): array;
    public function insertCategory(string $id, string $name, string $shape, int $width, int $height): bool;
    
    public function getTypes(): array;
    public function insertType(string $id, string $name): bool;

    public function getNode(string $id): ?array;
    public function getNodes(): array;
    public function getNodeParentOf(string $id): ?array;
    public function getDependentNodesOf(string $id): array;
    public function insertNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function updateNode(string $id, string $label, string $category, string $type, array $data = []): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?array;
    public function getEdges(): array;
    public function insertEdge(string $id, string $source, string $target, array $data = []): bool;
    public function updateEdge(string $id, string $source, string $target, array $data = []): bool;
    public function deleteEdge(string $id): bool;

    public function getStatus(): array;
    public function getNodeStatus(string $id): ?array;
    public function updateNodeStatus(string $id, string $status): bool;

    public function getLogs(int $limit): array;
    public function insertLog(string $entity_type, string $entity_id, string $action, ?array $old_data = null, ?array $new_data = null, string $user_id, string $ip_address): bool;
}
#####################################

class HTTPNotFoundResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(404, 'error', $message, $data);
    }
}
#####################################

class HTTPOKResponse extends HTTPResponse
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, 'success', $message, $data);
    }
}

#####################################

class HTTPForbiddenResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(403, 'error', $message, $data);
    }
}
#####################################

final class HTTPRequest
{
    public array $data;
    public array $params;
    public string $path;
    public string $method;

    public function __construct()
    {
        $this->params = $_GET;

        $this->method = $_SERVER['REQUEST_METHOD'];

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        $path = str_replace($scriptName, '', $requestUri);
        $this->path = $path;

        if ($this->method === 'POST' || $this->method === 'PUT') {
            $jsonData = file_get_contents('php://input');
            if ($jsonData) {
                $this->data = json_decode($jsonData, true); 
            } else {
                $this->data = [];
            }
        }
    }

    public function getParam($name): string
    {
        if(isset($this->params[$name])) {
            return strval($this->params[$name]);
        }
        throw new HTTPRequestException("param '{$name}' is missing", [], $this->params, $this->path);
    }
}
#####################################

class HTTPCreatedResponse extends HTTPResponse
{
    public function __construct(string $message = '', array $data)
    {
        return parent::__construct(201, 'success', $message, $data);
    }
}

#####################################

final class ModelStatus
{
    private const ALLOWED_NODE_STATUSES = ['unknown', 'healthy', 'unhealthy', 'maintenance'];

    private string $nodeId;
    private string $status;

    public function __construct(string $nodeId, string $status)
    {
        if (!in_array($status, self::ALLOWED_NODE_STATUSES, true)) {
            throw new InvalidArgumentException("Invalid node status: {$status}");
        }
        $this->nodeId = $nodeId;
        $this->status = $status;
    }

    public function getNodeId(): string
    {
        return $this->nodeId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'node_id' => $this->nodeId,
            'status'  => $this->status,
        ];
    }
}
#####################################

final class ModelCategory
{
    public string $id;
    public string $name;
    public string $shape;
    public int $width;
    public int $height;

    public function __construct(string $id, string $name, string $shape, int $width, int $height)
    {
        $this->id = $id;
        $this->name = $name;
        $this->shape = $shape;
        $this->width = $width;
        $this->height = $height;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'shape' => $this->shape,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
#####################################

abstract class TestAbstractTest
{
    protected function up(): void
    {
    }

    protected function down(): void
    {
    }

    public function run(): void
    {
        $ref = new ReflectionClass($this);
        $methods = $ref->getMethods();
        
        foreach($methods as $method)
        {
            $methodName = $method->getName();
            if(str_starts_with($methodName, 'test'))
            {
                $this->runTest($methodName);
            }
        }
    }

    private function runTest($testName): void
    {
        $class = get_class($this);

        try
        {
            $this->up();
            $this->$testName();
            $this->down();
        } catch(Exception $e) {
            print("{$class} {$testName}\n");
            throw new Exception("Exception found in test '{$testName}'. ({$e->getMessage()})\n");
        }
    }
}

#####################################

final class ModelUser
{
    private string $id;
    private ModelGroup $group;

    public function __construct(string $id, ModelGroup $group)
    {
        $this->id = $id;
        $this->group = $group;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): ModelGroup
    {
        return $this->group;
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'group' => $this->group->toArray(),
        ];
    }
}

#####################################

final class HTTPRequestRouter
{
    private $routes = [
        ['method' => 'GET',    'path' => '/getUser',          'class_method' => 'getUser'],
        ['method' => 'POST',   'path' => '/insertUser',       'class_method' => 'insertUser'],
        ['method' => 'PUT',    'path' => '/updateUser',       'class_method' => 'updateUser'],
        ['method' => 'GET',    'path' => '/getGraph',         'class_method' => 'getGraph'],
        ['method' => 'GET',    'path' => '/getNode',          'class_method' => 'getNode'],
        ['method' => 'GET',    'path' => '/getNodes',         'class_method' => 'getNodes'],
        ['method' => 'POST',   'path' => '/insertNode',       'class_method' => 'insertNode'],
        ['method' => 'PUT',    'path' => '/updateNode',       'class_method' => 'updateNode'],
        ['method' => 'DELETE', 'path' => '/deleteNode',       'class_method' => 'deleteNode'],
        ['method' => 'GET',    'path' => '/getEdge',          'class_method' => 'getEdge'],
        ['method' => 'GET',    'path' => '/getEdges',         'class_method' => 'getEdges'],
        ['method' => 'POST',   'path' => '/insertEdge',       'class_method' => 'insertEdge'],
        ['method' => 'PUT',    'path' => '/updateEdge',       'class_method' => 'updateEdge'],
        ['method' => 'DELETE', 'path' => '/deleteEdge',       'class_method' => 'deleteEdge'],
        ['method' => 'GET',    'path' => '/getStatuses',      'class_method' => 'getStatuses'],
        ['method' => 'GET',    'path' => '/getNodeStatus',    'class_method' => 'getNodeStatus'],
        ['method' => 'PUT',    'path' => '/updateNodeStatus', 'class_method' => 'updateNodeStatus'],
        ['method' => 'GET',    'path' => '/getLogs',          'class_method' => 'getLogs'],
    ];

    public HTTPController $controller;
    
    public function __construct(HTTPControllerInterface $controller)
    {
        $this->controller = $controller;
    }

    public function handle(HTTPRequest $req): HTTPResponse
    {
        foreach($this->routes as $route)
        {
            if ($route['method'] == $req->method && $route['path'] == $req->path)
            {
                $method = $route['class_method'];
                try {
                    $resp = $this->controller->$method($req);
                    return $resp;
                } catch (Exception $e) {
                    return new HTTPInternalServerErrorResponse("internal server error", ['exception_message' => $e->getMessage()]);
                }
            }
        }
        return new HTTPInternalServerErrorResponse("method not found in list", ['method' => $req->method, 'path' => $req->path]);
    }
}
#####################################

final class ModelEdge
{
    private string $source;
    private string $target;
    private array  $data;

    public function __construct(string $source, string $target, array $data = [])
    {
        $this->source = $source;
        $this->target = $target;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return "{$this->source}-{$this->target}";
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'target' => $this->target,
            'data'   => $this->data
        ];
    }
}
#####################################

final class HelperContext
{
    private static string $user;
    private static string $group;
    private static string $client_ip;

    public static function update(string $user, string $group, string $client_ip)
    {
        self::$user = $user;
        self::$group = $group;
        self::$client_ip = $client_ip;
    }

    public static function getUser(): string
    {
        return self::$user;
    }

    public static function getGroup(): string
    {
        return self::$group;
    }

    public static function getClientIP(): string
    {
        return self::$client_ip;
    }
}
#####################################

