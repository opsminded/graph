<?php

declare(strict_types=1);

class Response implements ResponseInterface
{
    public int     $code;
    public string  $status;
    public string  $message;
    public array   $data;
    public ?string $contentType;
    public ?string $binaryContent;
    public array   $headers;
    public ?string $template;

    public function __construct(int $code, string $status, string $message = "", array $data, ?string $contentType = null, ?string $binaryContent = null, array $headers = [], ?string $template = null)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
        $this->contentType = $contentType;
        $this->binaryContent = $binaryContent;
        $this->headers = $headers;
        $this->template = $template;
    }
}
#####################################

final class RequestRouter
{
    private $routes = [
        ["method" => Request::METHOD_GET,    "class_method" => "getUser"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertUser"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateUser"],
        ["method" => Request::METHOD_GET,    "class_method" => "getCategories"],
        ["method" => Request::METHOD_GET,    "class_method" => "getTypes"],
        ["method" => Request::METHOD_GET,    "class_method" => "getCytoscapeGraph"],
        ["method" => Request::METHOD_GET,    "class_method" => "getNode"],
        ["method" => Request::METHOD_GET,    "class_method" => "getNodes"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertNode"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateNode"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteNode"],
        ["method" => Request::METHOD_GET,    "class_method" => "getEdge"],
        ["method" => Request::METHOD_GET,    "class_method" => "getEdges"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertEdge"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateEdge"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteEdge"],
        ["method" => Request::METHOD_GET,    "class_method" => "getStatus"],
        ["method" => Request::METHOD_GET,    "class_method" => "getNodeStatus"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateNodeStatus"],

        ["method" => Request::METHOD_GET,    "class_method" => "getProject"],
        ["method" => Request::METHOD_GET,    "class_method" => "getProjects"],

        ["method" => Request::METHOD_GET,    "class_method" => "getSave"],
        ["method" => Request::METHOD_GET,    "class_method" => "getSaves"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertSave"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateSave"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteSave"],

        ["method" => Request::METHOD_GET,    "class_method" => "getLogs"],
    ];

    public Controller $controller;
    
    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function handle(Request $req): Response
    {
        $irregularResponse = $this->tryIrregularRoutes($req);
        if ($irregularResponse !== null) {
            return $irregularResponse;
        }

        $regularResponse = $this->tryRegularRoutes($req);
        if ($regularResponse !== null) {
            return $regularResponse;
        }

        return new InternalServerErrorResponse("method not found in list", ["method" => $req->method, "path" => $req->path]);
    }

    private function tryIrregularRoutes(Request $req): ?Response
    {
        if($req->method == Request::METHOD_GET && $req->path == "/")
        {
            $resp = new OKResponse("welcome to the API", []);
            $resp->template = 'editor.html';
            return $resp;
        }

        // if ($req->method == Request::METHOD_GET && $req->path == "/getImage")
        // {
        //     $type = $req->getParam('img');
        //     $types = HelperImages::getTypes();
        //     if (!in_array($type, $types)) {
        //         return new NotFoundResponse("image not found", ["requested_type" => $type, "available_types" => $types]);
        //     }
            
        //     $resp = new OKResponse("getImage", []);
        //     $resp->contentType = 'Content-Type: image/png';
        //     $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
        //     $resp->headers[] = "Content-Length: " . strlen($resp->binaryContent);

        //     $resp->headers[] = "Cache-Control: public, max-age=86400";
        //     $resp->headers[] = "Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT";
        //     $resp->headers[] = "ETag: \"" . HelperImages::getImageEtag($req->getParam('img')) . "\"";

        //     $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
        //     return $resp;
        // }
        return null;
    }

    private function tryRegularRoutes(Request $req): ?Response
    {
        foreach($this->routes as $route)
        {
            if ($route["method"] == $req->method && "/{$route["class_method"]}" == $req->path)
            {
                $method = $route["class_method"];
                try {
                    $resp = $this->controller->$method($req);
                    return $resp;
                } catch (Exception $e) {
                    return new InternalServerErrorResponse("internal server error", ["exception_message" => $e->getMessage()]);
                }
            }
        }
        return null;
    }
}
#####################################

final class Renderer
{
    private string $templateDir;
    
    public function __construct(string $templateDir)
    {
        $templateDir = rtrim($templateDir, '/');
        $this->templateDir = $templateDir;
    }

    public function render(Response $response): void
    {
        header("Access-Control-Allow-Origin: *");
        http_response_code($response->code);

        foreach ($response->headers as $header => $value) {
            header("$header: $value");
        }

        if ($response->binaryContent !== null) {
            header($response->contentType);
            echo base64_decode($response->binaryContent);
            exit();
        }

        if($response->template === null) {
            header('Content-Type: application/json; charset=utf-8');
            $data = [
                ResponseInterface::KEYNAME_CODE => $response->code,
                ResponseInterface::KEYNAME_STATUS => $response->status,
                ResponseInterface::KEYNAME_MESSAGE => $response->message,
                ResponseInterface::KEYNAME_DATA => $response->data
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
            exit();
        } else {
            header('Content-Type: text/html; charset=utf-8');
            $templatePath = $this->templateDir . '/' . $response->template;
            if (!file_exists($templatePath)) {
                echo $templatePath;
                exit();
                throw new Exception("template not found: " . $response->template);
            }

            ob_start();
            include $templatePath;
            $content = ob_get_clean();
            echo $content;
            exit();
        }
    }
}
#####################################

final class Graph
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
        $nodes = [];
        foreach ($this->nodes as $node) {
            $nodes[] = $node->toArray();
        }

        $edges = [];
        foreach ($this->edges as $edge) {
            $edges[] = $edge->toArray();
        }
        return [
            "nodes" => $nodes,
            "edges" => $edges,
        ];
    }
}
#####################################

final class Type
{
    private string $id;
    private string $name;

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
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

final class Category
{
    private string $id;
    private string $name;
    private string $shape;
    private int $width;
    private int $height;

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
        return get_object_vars($this);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getShape(): string
    {
        return $this->shape;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }
}
#####################################

final class Project
{
    private string $id;
    private string $name;
    private string $author;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;    
    private array $data = [];

    public function __construct(
        string $id,
        string $name,
        string $author,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $data = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->data = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'author' => $this->author,
            'created_at' => $this->createdAt->format(DateTime::ATOM),
            'updated_at' => $this->updatedAt->format(DateTime::ATOM),
            'data' => $this->data,
        ];
    }
}
#####################################

final class Group
{
    private const VALUE_ANONYMOUS   = "anonymous";
    private const VALUE_CONSUMER    = "consumer";
    private const VALUE_CONTRIBUTOR = "contributor";
    private const VALUE_ADMIN       = "admin";

    private const ALLOWED_GROUPS = [
        self::VALUE_ANONYMOUS,
        self::VALUE_CONSUMER,
        self::VALUE_CONTRIBUTOR,
        self::VALUE_ADMIN,
    ];
    
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

final class User
{
    private string $id;
    private Group $group;

    public function __construct(string $id, Group $group)
    {
        $this->id = $id;
        $this->group = $group;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getGroup(): Group
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

final class Node
{
    private string $id;
    private string $label;
    private string $categoryID;
    private string $typeID;
    private array $data = [];

    public const ID_VALIDATION_REGEX = "/^[a-zA-Z0-9\-_]+$/";
    public const LABEL_MAX_LENGTH    = 120;

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

final class Status
{
    public const STATUS_VALUE_UNKNOWN     = "unknown";
    public const STATUS_VALUE_HEALTHY     = "healthy";
    public const STATUS_VALUE_UNHEALTHY   = "unhealthy";
    public const STATUS_VALUE_MAINTENANCE = "maintenance";
    public const STATUS_VALUE_IMPACTED    = "impacted";

    private const ALLOWED_NODE_STATUS = [
        self::STATUS_VALUE_UNKNOWN,
        self::STATUS_VALUE_HEALTHY,
        self::STATUS_VALUE_UNHEALTHY,
        self::STATUS_VALUE_MAINTENANCE,
        self::STATUS_VALUE_IMPACTED,
    ];

    private string $nodeId;
    private string $status;

    public function __construct(string $nodeId, string $status)
    {
        if (!in_array($status, self::ALLOWED_NODE_STATUS, true)) {
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

final class Edge
{
    private string $id;
    private string $label;
    private string $source;
    private string $target;
    private array  $data;

    public function __construct(string $source, string $target, string $label, array $data = [])
    {
        $this->id     = "{$source}-{$target}";
        $this->source = $source;
        $this->target = $target;
        $this->label  = $label;
        $this->data   = $data;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
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
        return get_object_vars($this);
    }
}
#####################################

final class Log
{
    private string            $entityType;
    private string            $entityId;
    private string            $action;
    private ?array            $oldData;
    private ?array            $newData;
    private string            $userId;
    private string            $ipAddress;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldData = null,
        ?array $newData = null,
        string $userId,
        string $ipAddress,
        DateTimeImmutable $createdAt
    ) {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->action     = $action;
        $this->oldData    = $oldData;
        $this->newData    = $newData;
        $this->userId     = $userId;
        $this->ipAddress  = $ipAddress;
        $this->createdAt  = $createdAt;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getOldData(): ?array
    {
        return $this->oldData;
    }

    public function getNewData(): ?array
    {
        return $this->newData;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'action'      => $this->action,
            'old_data'    => $this->oldData,
            'new_data'    => $this->newData,
            'user_id'     => $this->userId,
            'ip_address'  => $this->ipAddress,
            'created_at'  => $this->createdAt,
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
            echo "{$class} {$testName}\n";
            throw new Exception("Exception found in test '{$testName}'. ({$e->getMessage()})\n");
        }
    }
}

#####################################

final class DatabaseException extends RuntimeException
{
    public function __construct(string $message, PDOException $exception)
    {
        $message = "Database Error: " . $message  .". Exception: ". $exception->getMessage();
        parent::__construct($message, 0, $exception);
    }
}
#####################################

final class Database implements DatabaseInterface
{
    private const SQLSTATE_CONSTRAINT_VIOLATION = '23000';

    public function __construct(
        private PDO $pdo,
        private LoggerInterface $logger,
        private string $sqlSchema
    ) {
        $this->initSchema();
    }

    public function getUser(string $id): ?UserDTO
    {
        $this->logger->debug("getting user id", ['id' => $id]);
        $sql = "SELECT id, user_group as \"group\" FROM users WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("user found", ['params' => $params, 'row' => $row]);
            return new UserDTO($row['id'], $row['group']);
        }
        $this->logger->info("user not found", ['params' => $params]);
        return null;
    }

    /**                                                                                                                                                                                    
     * @return UserDTO[]                                                                                                                                                                   
     */ 
    public function getUsers(): array
    {
        $this->logger->debug("fetching users");
        $sql = "SELECT id, user_group as \"group\" FROM users";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info("users fetched", ['rows' => $rows]);
        return array_map(fn($row) => new UserDTO($row['id'], $row['group']), $rows);
    }

    public function insertUser(UserDTO $user): bool
    {
        $this->logger->debug("inserting new user", ['id' => $user->id, 'group' => $user->group]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $params = [':id' => $user->id, ':group' => $user->group];
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("user already exists", ['params' => $params]);
                $complementary = " - user already exists";
            }
            throw new DatabaseException("Failed to insert user" . $complementary, $exception);
        }
    }

    /**
     * @param UserDTO[] $users
     */
    public function batchInsertUsers(array $users): bool
    {
        $this->logger->debug("batch inserting users", ['users' => $users]);
        $sql = "INSERT INTO users (id, user_group) VALUES (:id, :group)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($users as $user) {
            $params = [':id' => $user->id, ':group' => $user->group];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                    $complementary = "user already exists: ";
                    $complementary .= $user->id;
                    $this->logger->error("user already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to insert user in batch: " . $complementary, $exception);
            }
        }
        $this->logger->info("batch users inserted", ['users' => $users]);
        return true;
    }

    public function updateUser(UserDTO $user): bool
    {
        $this->logger->debug("updating user", ['id' => $user->id, 'group' => $user->group]);
        $sql = "UPDATE users SET user_group = :group WHERE id = :id";
        $params = [':id' => $user->id, ':group' => $user->group];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("user updated", ['params' => $params]);
            return true;
        }
        $this->logger->info("user not updated", ['params' => $params]);
        return false;
    }

    public function deleteUser(string $id): bool
    {
        $this->logger->debug("deleting user", ['id' => $id]);
        $sql = "DELETE FROM users WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("user deleted", ['params' => $params]);
            return true;
        }
        $this->logger->info("user not deleted", ['params' => $params]);
        return false;
    }

    public function getCategory(string $id): ?CategoryDTO
    {
        $this->logger->debug("fetching category", ['id' => $id]);
        $sql = "SELECT * FROM categories WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("category fetched", ['params' => $params, 'row' => $row]);
            return new CategoryDTO($row['id'], $row['name'], $row['shape'], (int)$row['width'], (int)$row['height']);
        }
        $this->logger->info("category not found", ['params' => $params]);
        return null;
    }

    /**
     * @return CategoryDTO[]
     */
    public function getCategories(): array
    {
        $this->logger->debug("fetching categories");
        $sql = "SELECT * FROM categories";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info("categories fetched", ['rows' => $rows]);
        return array_map(fn($row) => new CategoryDTO($row['id'], $row['name'], $row['shape'], (int)$row['width'], (int)$row['height']), $rows);
    }

    public function insertCategory(CategoryDTO $category): bool
    {
        $this->logger->debug("inserting new category", ['id' => $category->id, 'name' => $category->name]);
        $sql = "INSERT INTO categories (id, name, shape, width, height) VALUES (:id, :name, :shape, :width, :height)";
        $params = [':id' => $category->id, ':name' => $category->name, ':shape' => $category->shape, ':width' => $category->width, ':height' => $category->height];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("category inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("category already exists", ['params' => $params]);
                $complementary = "category already exists: " . $category->id;
            }
            throw new DatabaseException("Failed to insert category. " . $complementary, $exception);
        }
    }

    public function updateCategory(CategoryDTO $category): bool
    {
        $this->logger->debug("updating category", ['id' => $category->id, 'name' => $category->name, 'shape' => $category->shape, 'width' => $category->width, 'height' => $category->height]);
        $sql = "UPDATE categories SET name = :name, shape = :shape, width = :width, height = :height WHERE id = :id";
        $params = [':id' => $category->id, ':name' => $category->name, ':shape' => $category->shape, ':width' => $category->width, ':height' => $category->height];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("category updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("category not updated", ['params' => $params]);
        return false;
    }

    public function deleteCategory(string $id): bool
    {
        $this->logger->debug('deleting category', ['id' => $id]);
        $sql = "DELETE FROM categories WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("category deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("category not deleted", ['params' => $params]);
        return false;
    }

    public function getType(string $id): ?TypeDTO
    {
        $this->logger->debug("fetching type", ['id' => $id]);
        $sql = "SELECT * FROM types WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("type fetched", ['params' => $params, 'row' => $row]);
            return new TypeDTO($row['id'], $row['name']);
        }
        $this->logger->info("type not found", ['params' => $params]);
        return null;
    }
    
    /**
     * @return TypeDTO[]
     */
    public function getTypes(): array
    {
        $this->logger->debug("fetching types");
        $sql = "SELECT * FROM types";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $this->logger->info("types fetched", ['rows' => $rows]);
        return array_map(fn($row) => new TypeDTO($row['id'], $row['name']), $rows);
    }

    public function insertType(TypeDTO $type): bool
    {
        $this->logger->debug("inserting new type", ['id' => $type->id, 'name' => $type->name]);
        $sql = "INSERT INTO types (id, name) VALUES (:id, :name)";
        $params = [':id' => $type->id, ':name' => $type->name];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("type inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Type already exists", ['params' => $params]);
                $complementary = "Type already exists: " . $type->id;
            }
            throw new DatabaseException("Failed to insert type. " . $complementary, $exception);
        }
    }

    public function updateType(TypeDTO $type): bool
    {
        $this->logger->debug("updating type", ['id' => $type->id, 'name' => $type->name]);
        $sql = "UPDATE types SET name = :name WHERE id = :id";
        $params = [':id' => $type->id, ':name' => $type->name];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("type updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("type not updated", ['params' => $params]);
        return false;
    }

    public function deleteType(string $id): bool
    {
        $this->logger->debug("deleting type", ['id' => $id]);
        $sql = "DELETE FROM types WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("type deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("type not deleted", ['params' => $params]);
        return false;
    }

    public function getNode(string $id): ?NodeDTO
    {
        $this->logger->debug("fetching node", ['id' => $id]);
        $sql = "SELECT * FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("node fetched", ['params' => $params, 'row' => $row]);
            return new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row['data']);
        }
        $this->logger->info("node not found", ['params' => $params]);
        return null;
    }

    /**
     * @return NodeDTO[]
     */
    public function getNodes(): array
    {
        $this->logger->debug("fetching nodes");
        $sql = "SELECT * FROM nodes";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        $this->logger->info("nodes fetched", ['rows' => $rows]);
        return array_map(fn($row) => new NodeDTO($row['id'], $row['label'], $row['category'], $row['type'], $row['data']), $rows);
    }

    public function insertNode(NodeDTO $node): bool
    {
        $this->logger->debug("inserting new node", ['id' => $node->id, 'label' => $node->label, 'category' => $node->category, 'type' => $node->type, 'data' => $node->data]);
        $sql = "INSERT INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)";
        $data = json_encode($node->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $node->id, ':label' => $node->label, ':category' => $node->category, ':type' => $node->type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("node inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Node already exists", ['params' => $params]);
                $complementary = "Node already exists: " . $node->id;
            }
            throw new DatabaseException("Failed to insert node. " . $complementary, $exception);
        }
    }

    /**
     * @param NodeDTO[] $nodes
     */
    public function batchInsertNodes(array $nodes): bool
    {
        $this->logger->debug("batch inserting nodes", ['nodes' => $nodes]);
        $sql = "INSERT INTO nodes (id, label, category, type, data) VALUES (:id, :label, :category, :type, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($nodes as $node) {
            $data = json_encode($node->data ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $node->id,
                ':label' => $node->label,
                ':category' => $node->category,
                ':type' => $node->type,
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                    $complementary = "Node already exists: ";
                    $complementary .= $node->id;
                    $this->logger->error("node already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to batch insert nodes. " . $complementary, $exception);
            }
        }
        $this->logger->info("batch nodes inserted", ['nodes' => $nodes]);
        return true;
    }

    public function updateNode(NodeDTO $node): bool
    {
        $this->logger->debug("updating node", ['id' => $node->id, 'label' => $node->label, 'category' => $node->category, 'type' => $node->type, 'data' => $node->data]);
        $sql = "UPDATE nodes SET label = :label, category = :category, type = :type, data = :data WHERE id = :id";
        $data = json_encode($node->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $node->id, ':label' => $node->label, ':category' => $node->category, ':type' => $node->type, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("node updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("node not updated", ['params' => $params]);
        return false;
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug("deleting node", ['id' => $id]);
        $sql = "DELETE FROM nodes WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->debug("node deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("node not deleted", ['params' => $params]);
        return false;
    }

    public function getEdge(string $id): ?EdgeDTO
    {
        $this->logger->debug("getting edge", ['id' => $id]);
        $sql = "SELECT * FROM edges WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $row['data'] = json_decode($row['data'], true);
            $this->logger->info("edge found", ['params' => $params, 'row' => $row]);
            return new EdgeDTO($row['id'], $row['source'], $row['target'], $row['label'], $row['data']);
        }
        $this->logger->info("edge not found", ['params' => $params]);
        return null;
    }

    /**
     * @return EdgeDTO[]
     */
    public function getEdges(): array
    {
        $this->logger->debug("fetching edges");
        $sql = "SELECT * FROM edges";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();

        $edges = [];
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
            $edges[] = new EdgeDTO($row['id'], $row['source'], $row['target'], $row['label'], $row['data']);
        }
        $this->logger->info("edges fetched", ['rows' => $rows]);
        return $edges;
    }

    public function insertEdge(EdgeDTO $edge): bool
    {
        $this->logger->debug("inserting edge", ['id' => $edge->id, 'source' => $edge->source, 'target' => $edge->target, 'label' => $edge->label, 'data' => $edge->data]);

        if ($this->wouldCreateCycle($edge->source, $edge->target)) {
            $this->logger->error("Circular reference detected", ['source' => $edge->source, 'target' => $edge->target]);
            throw new DatabaseException("Cannot insert edge: would create circular reference", new Exception());
        }

        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $data = json_encode($edge->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $edge->id, ':source' => $edge->source, ':target' => $edge->target, ':label' => $edge->label, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute($params);
            $this->logger->info("edge inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Edge already exists", ['params' => $params]);
                $complementary = "Edge already exists: " . $edge->id;
            }
            throw new DatabaseException("Failed to insert edge. " . $complementary, $exception);
        }
    }

    /**
     * @param EdgeDTO[] $edges
     */
    public function batchInsertEdges(array $edges): bool
    {
        $this->logger->debug("batch inserting edges", ['edges' => $edges]);

        foreach ($edges as $edge) {
            if ($this->wouldCreateCycle($edge->source, $edge->target)) {
                $this->logger->error("Circular reference detected in batch", ['source' => $edge->source, 'target' => $edge->target]);
                throw new DatabaseException("Cannot insert edge: would create circular reference", new Exception());
            }
        }

        $sql = "INSERT INTO edges(id, source, target, label, data) VALUES (:id, :source, :target, :label, :data)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($edges as $edge) {
            $data = json_encode($edge->data ?? [], JSON_UNESCAPED_UNICODE);
            $params = [
                ':id' => $edge->id,
                ':source' => $edge->source,
                ':target' => $edge->target,
                ':label' => $edge->label,
                ':data' => $data
            ];
            try {
                $stmt->execute($params);
            } catch (PDOException $exception) {
                $complementary = "";
                if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                    $complementary = "Edge already exists: ";
                    $complementary .= $edge->id;
                    $this->logger->error("edge already exists in batch", ['params' => $params]);
                }
                throw new DatabaseException("Failed to batch insert edges. " . $complementary, $exception);
            }
        }
        $this->logger->info("batch edges inserted", ['edges' => $edges]);
        return true;
    }

    public function updateEdge(EdgeDTO $edge): bool
    {
        $this->logger->debug("updating edge", ['id' => $edge->id, 'label' => $edge->label, 'data' => $edge->data]);
        $sql = "UPDATE edges SET label = :label, data = :data WHERE id = :id";
        $data = json_encode($edge->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $edge->id, ':label' => $edge->label, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("edge updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("edge not updated", ['params' => $params]);
        return false;
    }

    public function deleteEdge(string $id): bool
    {
        $this->logger->debug("deleting edge", ['id' => $id]);
        $sql = "DELETE FROM edges WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("edge deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("edge not deleted", ['params' => $params]);
        return false;
    }

    public function getNodeStatus(string $id): ?StatusDTO
    {
        $this->logger->debug("fetching node status", ['id' => $id]);
        $sql = "SELECT n.id, s.status FROM nodes n LEFT JOIN status s ON n.id = s.node_id WHERE n.id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $this->logger->info("node status fetched", ['params' => $params, 'row' => $row]);
            return new StatusDTO($row['id'], $row['status']);
        }
        return null;
    }

    public function updateNodeStatus(StatusDTO $status): bool
    {
        $this->logger->debug("updating node status", ['id' => $status->node_id, 'status' => $status->status]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $params = [':node_id' => $status->node_id, ':status' => $status->status];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $complementary .= "node not found for status update: " . $status->node_id;
                $this->logger->error("Node not found for status update", ['params' => $params]);
            }
            throw new DatabaseException("Failed to update node status: " . $complementary, $exception);
        }
        $this->logger->info("node status updated", ['params' => $params]);
        return true;
    }

    /**
     * @param StatusDTO[] $statuses
     */
    public function batchUpdateNodeStatus(array $statuses): bool
    {
        $this->logger->debug("batch updating node statuses", ['statuses' => $statuses]);
        $sql = "REPLACE INTO status (node_id, status) VALUES (:node_id, :status)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($statuses as $status) {
            $params = [':node_id' => $status->node_id, ':status' => $status->status];
            $stmt->execute($params);
        }
        $this->logger->info("batch node statuses updated", ['statuses' => $statuses]);
        return true;
    }

    public function getProject(string $id): ?ProjectDTO
    {
        $this->logger->debug("fetching project", ['id' => $id]);
        $sql = "SELECT * FROM projects WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row) {
            $graph = $this->getProjectGraph($id);
            $project = new ProjectDTO(
                $row['id'],
                $row['name'],
                $row['author'],
                new DateTimeImmutable($row['created_at']),
                new DateTimeImmutable($row['updated_at']),
                json_decode($row['data'], true)
            );

            return $project;
        }
        $this->logger->info("project not found", ['params' => $params]);
        return null;
    }

    public function getProjectGraph(string $projectId): ?GraphDTO
    {
        $this->logger->debug("fetching project graph", ['project_id' => $projectId]);
        $sql = '
        WITH RECURSIVE descendants AS (
            SELECT      p.id     as project_id,
                        e.id     as edge_id,
                        e.label  as edge_label,
                        e.source as edge_source_id,
                        e.target as edge_target_id,
                        e.data   as edge_data,
                        0        as edge_depth
            FROM        edges e
            INNER JOIN  nodes_projects np
            ON          e.source = np.node_id
            INNER JOIN  projects p
            ON          np.project_id = p.id
            WHERE       p.id = :project_id
            UNION ALL
            SELECT      d.project_id     as project_id,
                        e.id             as edge_id,
                        e.label          as edge_label,
                        e.source         as edge_source_id,
                        e.target         as edge_target_id,
                        e.data           as edge_data,
                        d.edge_depth + 1 as edge_depth
            FROM        descendants d
            INNER JOIN  edges e ON d.edge_target_id = e.source
        )
        SELECT DISTINCT d.project_id,
                        d.edge_id,
                        d.edge_label,
                        d.edge_data,
                        d.edge_source_id,
                        s.label           as source_label,
                        s.category        as source_category,
                        s.type            as source_type,
                        s.data            as source_data,
                        d.edge_target_id,
                        t.label           as target_label,
                        t.category        as target_category,
                        t.type            as target_type,
                        t.data            as target_data,
                        min(d.edge_depth) as depth
        FROM            descendants d
        INNER JOIN      nodes s
        ON              d.edge_source_id = s.id
        INNER JOIN      nodes t
        ON              d.edge_target_id = t.id
        GROUP BY        d.project_id,
                        d.edge_id,
                        d.edge_label,
                        d.edge_data,
                        d.edge_source_id,
                        s.label,
                        s.category,
                        s.type,
                        s.data,
                        d.edge_target_id,
                        t.label,
                        t.category,
                        t.type,
                        t.data
        ORDER BY        depth,
                        d.project_id;
        ';
        $params = [':project_id' => $projectId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $this->logger->info("project Graph fetched", ['params' => $params, 'rows' => $rows]);
        $nodes = [];
        $edges = [];
        foreach ($rows as $row) {
            $nodes[] = new NodeDTO(
                $row['edge_source_id'], 
                $row['source_label'], 
                $row['source_category'],
                $row['source_type'],
                json_decode($row['source_data'], true)
            );

            $nodes[] = new NodeDTO(
                $row['edge_target_id'], 
                $row['target_label'], 
                $row['target_category'],
                $row['target_type'],
                json_decode($row['target_data'], true)
            );

            $edges[] = new EdgeDTO(
                $row['edge_id'],
                $row['edge_source_id'],
                $row['edge_target_id'],
                $row['edge_label'],
                json_decode($row['edge_data'], true)
            );
        }

        return new GraphDTO($nodes, $edges);
    }

    /**
     * @return ProjectDTO[]
     */
    public function getProjects(): array
    {
        $this->logger->debug("fetching projects");
        $sql = "SELECT * FROM projects";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll();
        $projects = [];
        foreach ($rows as $row) {
            $row['data'] = json_decode($row['data'], true);
            $projects[] = new ProjectDTO(
                $row['id'],
                $row['name'],
                $row['author'],
                new DateTimeImmutable($row['created_at']),
                new DateTimeImmutable($row['updated_at']),
                $row['data']
            );
        }
        $this->logger->info("projects fetched", ['rows' => $rows]);
        return $projects;
    }

    public function insertProject(ProjectDTO $project): bool
    {
        $this->logger->debug("inserting new project", ['id' => $project->id, 'name' => $project->name, 'author' => $project->author, 'data' => $project->data]);
        $sql = "INSERT INTO projects (id, name, author, data) VALUES (:id, :name, :author, :data)";
        $data = json_encode($project->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $project->id, ':name' => $project->name, ':author' => $project->author, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("project inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            throw new DatabaseException("Failed to insert project", $exception);
        }
    }

    public function updateProject(ProjectDTO $project): bool
    {
        $this->logger->debug("updating project", ['id' => $project->id, 'name' => $project->name, 'author' => $project->author, 'data' => $project->data]);
        $sql = "UPDATE projects SET name = :name, author = :author, data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $data = json_encode($project->data, JSON_UNESCAPED_UNICODE);
        $params = [':id' => $project->id, ':name' => $project->name, ':author' => $project->author, ':data' => $data];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project updated", ['params' => $params]);
            return true;
        }
        $this->logger->error("project not updated", ['params' => $params]);
        return false;
    }

    public function deleteProject(string $id): bool
    {
        $this->logger->debug("deleting project", ['id' => $id]);
        $sql = "DELETE FROM projects WHERE id = :id";
        $params = [':id' => $id];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("project not deleted", ['params' => $params]);
        return false;
    }

    public function insertProjectNode(string $projectId, string $nodeId): bool
    {
        $this->logger->debug('inserting project node', ['project_id' => $projectId, 'node_id' => $nodeId]);
        $sql = "INSERT INTO nodes_projects (project_id, node_id) VALUES (:project_id, :node_id)";
        $params = [':project_id' => $projectId, ':node_id' => $nodeId];
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $this->logger->info("project node inserted", ['params' => $params]);
            return true;
        } catch (PDOException $exception) {
            $complementary = "";
            if ($exception->getCode() === self::SQLSTATE_CONSTRAINT_VIOLATION) {
                $this->logger->error("Project node already exists", ['params' => $params]);
                return false;
            }
            throw new DatabaseException("Failed to insert project node. " . $complementary, $exception);
        }
    }

    public function deleteProjectNode(string $projectId, string $nodeId): bool
    {
        $this->logger->debug('deleting project node', ['project_id' => $projectId, 'node_id' => $nodeId]);
        $sql = "DELETE FROM nodes_projects WHERE project_id = :project_id AND node_id = :node_id";
        $params = [':project_id' => $projectId, ':node_id' => $nodeId];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $this->logger->info("project node deleted", ['params' => $params]);
            return true;
        }
        $this->logger->error("project node not deleted", ['params' => $params]);
        return false;
    }

    /**
     * @return LogDTO[]
     */
    public function getLogs(int $limit): array
    {
        $this->logger->debug("fetching logs", ['limit' => $limit]);
        $sql = "SELECT * FROM audit ORDER BY created_at DESC LIMIT :limit";
        $params = [':limit' => $limit];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $logs = [];
        foreach ($rows as $row) {
            $logs[] = new LogDTO(
                $row['entity_type'],
                $row['entity_id'],
                $row['action'],
                $row['old_data'] !== null ? json_decode($row['old_data'], true) : null,
                $row['new_data'] !== null ? json_decode($row['new_data'], true) : null,
                $row['user_id'],
                $row['ip_address'],
                new DateTimeImmutable($row['created_at'])
            );
        }

        $this->logger->info("logs fetched", ['params' => $params, 'rows' => $rows]);
        return $logs;
    }

    public function insertLog(LogDTO $log): bool
    {
        $this->logger->debug("inserting audit log", ['entity_type' => $log->entityType, 'entity_id' => $log->entityId, 'action' => $log->action, 'old_data' => $log->oldData, 'new_data' => $log->newData, 'user_id' => $log->userId, 'ip_address' => $log->ipAddress]);
        $sql = "INSERT INTO audit (entity_type, entity_id, action, old_data, new_data, user_id, ip_address) VALUES (:entity_type, :entity_id, :action, :old_data, :new_data, :user_id, :ip_address)";
        $old_data = $log->oldData !== null ? json_encode($log->oldData, JSON_UNESCAPED_UNICODE) : null;
        $new_data = $log->newData !== null ? json_encode($log->newData, JSON_UNESCAPED_UNICODE) : null;
        $params = [':entity_type' => $log->entityType, ':entity_id' => $log->entityId, ':action' => $log->action, ':old_data' => $old_data, ':new_data' => $new_data, ':user_id' => $log->userId, ':ip_address' => $log->ipAddress];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $this->logger->info("audit log inserted", ['params' => $params]);
        return true;
    }

    private function wouldCreateCycle(string $source, string $target): bool
    {
        if ($source === $target) {
            return true;
        }

        $sql = '
        WITH RECURSIVE path AS (
            SELECT target as node_id, 1 as depth
            FROM edges
            WHERE source = :target
            UNION ALL
            SELECT e.target, p.depth + 1
            FROM edges e
            INNER JOIN path p ON e.source = p.node_id
        )
        SELECT 1 FROM path WHERE node_id = :source LIMIT 1
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':target' => $target, ':source' => $source]);

        return $stmt->fetch() !== false;
    }

    private function initSchema(): void
    {
        $this->pdo->exec($this->sqlSchema);
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

interface DatabaseInterface
{
    public const DATABASE_KEYWORD_LIMIT = "limit";

    public function getUser(string $id): ?UserDTO;

    /**
     * @return UserDTO[]
     */
    public function getUsers(): array;

    public function insertUser(UserDTO $user): bool;

    /**
     * @param UserDTO[] $users
     */
    public function batchInsertUsers(array $users): bool;

    public function updateUser(UserDTO $user): bool;
    public function deleteUser(string $id): bool;

    public function getCategory(string $id): ?CategoryDTO;

    /**
     * @return CategoryDTO[]
     */
    public function getCategories(): array;

    public function insertCategory(CategoryDTO $category): bool;
    public function updateCategory(CategoryDTO $category): bool;
    public function deleteCategory(string $id): bool;

    public function getType(string $id): ?TypeDTO;

    /**
     * @return TypeDTO[]
     */
    public function getTypes(): array;

    public function insertType(TypeDTO $type): bool;
    public function updateType(TypeDTO $type): bool;
    public function deleteType(string $id): bool;

    public function getNode(string $id): ?NodeDTO;

    /**
     * @return NodeDTO[]
     */
    public function getNodes(): array;

    public function insertNode(NodeDTO $node): bool;

    /**
     * @param NodeDTO[] $nodes
     */
    public function batchInsertNodes(array $nodes): bool;

    public function updateNode(NodeDTO $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $id): ?EdgeDTO;

    /**
     * @return EdgeDTO[]
     */
    public function getEdges(): array;

    public function insertEdge(EdgeDTO $edge): bool;

    /**
     * @param EdgeDTO[] $edges
     */
    public function batchInsertEdges(array $edges): bool;

    public function updateEdge(EdgeDTO $edge): bool;
    public function deleteEdge(string $id): bool;

    public function getNodeStatus(string $id): ?StatusDTO;
    public function updateNodeStatus(StatusDTO $status): bool;

    /**
     * @param StatusDTO[] $statuses
     */
    public function batchUpdateNodeStatus(array $statuses): bool;

    public function getProject(string $id): ?ProjectDTO;

    public function getProjectGraph(string $projectId): ?GraphDTO;

    /**
     * @return ProjectDTO[]
     */
    public function getProjects(): array;
    
    public function insertProject(ProjectDTO $project): bool;
    public function updateProject(ProjectDTO $project): bool;
    public function deleteProject(string $id): bool;
    public function insertProjectNode(string $projectId, string $nodeId): bool;
    public function deleteProjectNode(string $projectId, string $nodeId): bool;

    /**
     * @return LogDTO[]
     */
    public function getLogs(int $limit): array;

    public function insertLog(LogDTO $log): bool;
}
#####################################

interface LoggerInterface
{
    public function info(string $message, array $data = []): void;
    public function debug(string $message, array $data = []): void;
    public function error(string $message, array $data = []): void;
}
#####################################

// Config class for application settings
final class Config
{
    public static $env = 'production';
}

#####################################

final class Logger implements LoggerInterface
{
    private int $level = 3;

    public const LOGGER_LEVEL_DEBUG = 1;
    public const LOGGER_LEVEL_INFO  = 2;
    public const LOGGER_LEVEL_ERROR = 3;

    private static array $levelNames = [
        self::LOGGER_LEVEL_DEBUG => 'DEBUG',
        self::LOGGER_LEVEL_INFO  => 'INFO',
        self::LOGGER_LEVEL_ERROR => 'ERROR',
    ];

    public function __construct(int $level = 3)
    {
        $this->level = $level;
    }

    public function info(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_INFO, $message, $data);
        
    }

    public function debug(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_DEBUG, $message, $data);
    }

    public function error(string $message, array $data = []): void
    {
        $this->log(self::LOGGER_LEVEL_ERROR, $message, $data);
    }

    private function log(int $type, $message, $data = [])
    {
        if ($type < $this->level) {
            return;
        }

        $level = self::$levelNames[$type] ?? 'UNKNOWN';

        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $method = "{$trace[2]['class']}::{$trace[2]['function']}";
        $data = json_encode($data);
        $message = "[{$level}] {$method}: $message ($data)\n";
        error_log($message);
    }
}
#####################################

final class Service implements ServiceInterface
{
    private const SECURE_ACTIONS = [
        "Service::getUser"             => true,
        "Service::getCategories"       => true,
        "Service::getTypes"            => true,
        "Service::getNode"             => true,
        "Service::getNodes"            => true,
        "Service::getNodeParentOf"     => true,
        "Service::getDependentNodesOf" => true,
        "Service::getEdge"             => true,
        "Service::getEdges"            => true,
        "Service::getStatus"           => true,
        "Service::getNodeStatus"       => true,
        "Service::getProject"          => true,
        "Service::getProjects"         => true,
        "Service::getLogs"             => true,
        "Service::insertUser"          => false,
        "Service::updateUser"          => false,
        "Service::insertCategory"      => false,
        "Service::insertType"          => false,
        "Service::insertNode"          => false,
        "Service::updateNode"          => false,
        "Service::deleteNode"          => false,
        "Service::insertEdge"          => false,
        "Service::updateEdge"          => false,
        "Service::deleteEdge"          => false,
        "Service::updateNodeStatus"    => false,
        "Service::insertProject"       => false,
        "Service::updateProject"       => false,
        "Service::deleteProject"       => false,
        "Service::insertLog"           => false,
    ];

    private const ADMIN_ACTIONS = [
        "Service::insertUser",
        "Service::updateUser",
        "Service::insertCategory",
        "Service::insertType",
    ];

    private DatabaseInterface $database;
    private Logger $logger;

    public function __construct(DatabaseInterface $database, Logger $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    public function getUser(string $id): ?User
    {
        $this->logger->debug("getting user", ['id' => $id]);
        $this->verify();
        $dbUser = $this->database->getUser($id);
        if (! is_null($dbUser)) {
            $g = new Group($dbUser->group);
            $user = new User($id, $g);
            $this->logger->debug("user found", ['id' => $id, "user" => $dbUser]);
            return $user;
        }
        $this->logger->debug("user not found", ['id' => $id]);
        return null;
    }

    public function insertUser(User $user): bool
    {
        $this->logger->debug("inserting user", ["user" => $user->toArray()]);
        $this->verify();

        $dto = new UserDTO($user->getId(), $user->getGroup()->getId());

        $this->database->insertUser($dto);
        $this->logger->debug("user inserted", ["user" => $user->toArray()]);
        return true;
    }

    public function updateUser(User $user): bool
    {
        $this->logger->debug("updating user", ["user" => $user->toArray()]);
        $this->verify();

        $dto = new UserDTO($user->getId(), $user->getGroup()->getId());

        if ($this->database->updateUser($dto)) {
            $this->logger->debug("user updated", ["user" => $user->toArray()]);
            return true;
        }
        return false;
    }

    public function getCategories(): array
    {
        $this->logger->debug("getting categories");
        $this->verify();
        $dbCategories = $this->database->getCategories();
        $categories     = [];
        foreach ($dbCategories as $ctg) {
            $category = new Category(
                $ctg->id,
                $ctg->name,
                $ctg->shape,
                $ctg->width,
                $ctg->height
            );
            $categories[] = $category;
        }
        return $categories;
    }

    public function insertCategory(Category $category): bool
    {
        $this->logger->debug("inserting category", ["category" => $category->toArray()]);
        $this->verify();
        $dto = new CategoryDTO($category->getId(), $category->getName(), $category->getShape(), $category->getWidth(), $category->getHeight());
        if ($this->database->insertCategory($dto)) {
            $this->logger->info("category inserted", ["category" => $category->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertCategory");
    }
    
    public function getTypes(): array
    {
        $this->logger->debug("getting types");
        $this->verify();
        $dbTypes = $this->database->getTypes();
        $types     = [];
        foreach ($dbTypes as $tp) {
            $type = new Type(
                $tp->id,
                $tp->name
            );
            $types[] = $type;
        }
        return $types;
    }
    public function insertType(Type $type): bool
    {
        $this->logger->debug("inserting type", ["type" => $type->toArray()]);
        $this->verify();
        $dto = new TypeDTO($type->getId(), $type->getName());
        if ($this->database->insertType($dto)) {
            $this->logger->info("type inserted", ["type" => $type->toArray()]);
            return true;
        }
        return false;
    }

    public function getNode(string $id): ?Node
    {
        $this->logger->debug("getting node", ["id" => $id]);
        $this->verify();
        $node = $this->database->getNode($id);
        if (! is_null($node)) {
            return new Node(
                $node->id,
                $node->label,
                $node->category,
                $node->type,
                $node->data
            );
        }
        return null;
    }

    public function getNodes(): array
    {
        $this->logger->debug("getting nodes");
        $this->verify();
        $nodesData = $this->database->getNodes();
        $nodes     = [];
        foreach ($nodesData as $node) {
            $new = new Node(
                $node->id,
                $node->label,
                $node->category,
                $node->type,
                $node->data
            );
            $nodes[] = $new;
        }
        return $nodes;
    }

    public function insertNode(Node $node): bool
    {
        $this->logger->debug("inserting node", $node->toArray());
        $this->verify();
        $this->logger->debug("permission allowed", $node->toArray());
        $this->insertLog("node", $node->getId(), "insert", null, $node->toArray());

        $dto = new NodeDTO($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData());

        if ($this->database->insertNode($dto)) {
            $this->logger->info("node inserted", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertNode");
    }

    public function updateNode(Node $node): bool
    {
        $this->logger->debug("updating node", ["node" => $node->toArray()]);
        $this->verify();
        $exists = $this->database->getNode($node->getId());
        if (is_null($exists)) {
            $this->logger->error("node not found", $node->toArray());
            return false;
        }
        $old = $this->getNode($node->getId());
        $this->insertLog("node", $node->getId(), "update", $old->toArray(), $node->toArray());
        $dto = new NodeDTO($node->getId(), $node->getLabel(), $node->getCategory(), $node->getType(), $node->getData());
        if ($this->database->updateNode($dto)) {
            $this->logger->info("node updated", $node->toArray());
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNode");
    }

    public function deleteNode(string $id): bool
    {
        $this->logger->debug("deleting node", ["id" => $id]);
        $this->verify();
        $exists = $this->database->getNode($id);
        if (is_null($exists)) {
            $this->logger->error("node not found", ["id" => $id]);
            return false;
        }
        $old = $this->getNode($id);
        $this->insertLog("node", $id, "delete", $old->toArray(), null);
        if ($this->database->deleteNode($id)) {
            $this->logger->info("node deleted", ['id' => $id]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteNode");
    }

    public function getEdge(string $source, string $target): ?Edge
    {
        $this->logger->debug("getting edge", ['source' => $source, 'target' => $target]);
        $this->verify();
        $id = "{$source}-{$target}";
        $dbEdge = $this->database->getEdge($id);
        
        if (! is_null($dbEdge)) {
            $edge = new Edge(
                $dbEdge->source,
                $dbEdge->target,
                $dbEdge->label,
                $dbEdge->data
            );
            
            $data = $edge->toArray();
            $this->logger->info("edge found", $data);
            return $edge;
        }
        $this->logger->info("edge not found", ['source' => $source, 'target' => $target]);
        return null;
    }

    public function getEdges(): array
    {
        $this->logger->debug("getting edges");
        $this->verify();

        $dbEdges = $this->database->getEdges();
        $edges     = [];
        foreach ($dbEdges as $edge) {
            $edge = new Edge(
                $edge->source,
                $edge->target,
                $edge->label,
                $edge->data
            );
            $edges[] = $edge;
        }
        return $edges;
    }

    public function insertEdge(Edge $edge): bool
    {
        $this->logger->debug("inserting edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $this->insertLog("edge", $edge->getId(), "insert", null, $edge->toArray());
        $dto = new EdgeDTO($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getLabel(), $edge->getData());
        if ($this->database->insertEdge($dto)) {
            $this->logger->info("edge inserted", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::insertEdge");
    }

    public function updateEdge(Edge $edge): bool
    {
        $this->logger->debug("updating edge", ["edge" => $edge->toArray()]);
        $this->verify();
        $exists = $this->database->getEdge($edge->getId());
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["edge" => $edge->toArray()]);
            return false;
        }

        $old = $this->getEdge($edge->getSource(), $edge->getTarget());
        $this->insertLog("edge", $edge->getId(), "update", $old->toArray(), $edge->toArray());
        $dto = new EdgeDTO($edge->getId(), $edge->getSource(), $edge->getTarget(), $edge->getLabel(), $edge->getData());
        if ($this->database->updateEdge($dto)) {
            $this->logger->info("edge updated", ["edge" => $edge->toArray()]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateEdge");
    }

    public function deleteEdge(string $source, string $target): bool
    {
        $this->logger->debug("deleting edge", ["source" => $source, "target" => $target]);
        $this->verify();
        $id = "{$source}-{$target}";
        $exists = $this->database->getEdge($id);
        if (is_null($exists)) {
            $this->logger->error("edge not found", ["source" => $source, "target" => $target]);
            return false;
        }
        $old = $this->getEdge($source, $target);
        $this->insertLog("edge", "{$source}-{$target}", "delete", $old->toArray(), null);
        $id = "{$source}-{$target}";
        if ($this->database->deleteEdge($id)) {
            $this->logger->info("edge deleted", ["source" => $source, "target" => $target]);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::deleteEdge");
    }

    public function getNodeStatus(string $id): ?Status
    {
        $this->logger->debug("getting node status", ["id" => $id]);
        $this->verify();
        $nodeStatus = $this->database->getNodeStatus($id);

        if (! is_null($nodeStatus)) {
            $this->logger->info("status found", ['id' => $id]);
            return new Status($id, $nodeStatus->status ?? "unknown");
        }
        $this->logger->info("status not found", ["id" => $id]);
        return null;
    }

    public function updateNodeStatus(Status $status): bool
    {
        $this->logger->debug("updating node status", ["status" => $status->toArray()]);
        $this->verify();
        $data = $status->toArray();
        $dto = new StatusDTO($status->getNodeId(), $status->getStatus());
        if ($this->database->updateNodeStatus($dto)) {
            $this->logger->info("node status updated", $data);
            return true;
        }
        throw new RuntimeException("unexpected error on Service::updateNodeStatus");
    }

    public function getProject(string $id): ?Project
    {
        $this->logger->debug("getting project", ["id" => $id]);
        $this->verify();
        $dbProject = $this->database->getProject($id);

        if (! is_null($dbProject)) {
            $project = new Project(
                $dbProject->id,
                $dbProject->name,
                $dbProject->author,
                $dbProject->createdAt,
                $dbProject->updatedAt
            );
            $this->logger->info("project found", ["project" => $project]);
            return $project;
        }
        $this->logger->info("project not found", ["id" => $id]);
        return null;
    }

    public function getProjects(): array
    {
        $this->logger->debug("getting projects");
        $this->verify();
        $dbProjects = $this->database->getProjects();
        
        $projects     = [];

        foreach ($dbProjects as $project) {
            $project = new Project(
                $project->id,
                $project->name,
                $project->author,
                $project->createdAt,
                $project->updatedAt
            );
            $projects[] = $project;
        }
        return $projects;
    }

    public function insertProject(Project $project): bool
    {
        $this->logger->debug("inserting project", ["project" => $project->toArray()]);
        $this->verify();

        $dto = new ProjectDTO(
            $project->getId(),
            $project->getName(),
            $project->getAuthor(),
            $project->getCreatedAt(),
            $project->getUpdatedAt(),
            $project->getData()
        );
        return $this->database->insertProject($dto);
    }

    public function updateProject(Project $project): bool
    {
        $this->logger->debug("updating project", ["project" => $project->toArray()]);
        $this->verify();

        $dto = new ProjectDTO(
            $project->getId(),
            $project->getName(),
            $project->getAuthor(),
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $project->getData()
        );

        return $this->database->updateProject($dto);
    }

    public function deleteProject(string $id): bool
    {
        $this->logger->debug("deleting project", ["id" => $id]);
        $this->verify();
        return $this->database->deleteProject($id);
    }

    public function getLogs(int $limit): array
    {
        $this->logger->debug("getting logs", ["limit" => $limit]);
        $this->verify();
        
        $dbLogs = $this->database->getLogs($limit);
        
        $logs = [];
        foreach ($dbLogs as $log) {
            $log = new Log(
                $log->entityType,
                $log->entityId,
                $log->action,
                $log->oldData,
                $log->newData,
                $log->userId,
                $log->ipAddress,
                $log->timestamp
            );

            $logs[] = $log;
        }
        return $logs;
    }

    private function insertLog(
        string $entityType,
        string $entityId,
        string $action,
        ?array $oldData = null,
        ?array $newData = null
    ): void
    {
        $userId   = HelperContext::getUser();
        $ipAddress = HelperContext::getClientIP();

        $dto = new LogDTO(
            $entityType,
            $entityId,
            $action,
            $oldData,
            $newData,
            $userId,
            $ipAddress,
            new DateTimeImmutable()
        );
        
        $this->database->insertLog($dto);
    }

    private function verify(): void
    {
        $trace  = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        $action = "{$trace[1]['class']}::{$trace[1]['function']}";
        $group  = HelperContext::getGroup();

        $this->logger->debug("verify", ["action" => $action, "group" => $group]);

        if (! array_key_exists($action, self::SECURE_ACTIONS)) {
            $this->logger->error("action not found in SECURE_ACTIONS", ["action" => $action]);
            throw new RuntimeException("action not found in SECURE_ACTIONS: " . $action);
        }

        if (in_array($action, self::ADMIN_ACTIONS, true) && $group !== "admin") {
            $this->logger->info("only admin allowed", ["action" => $action, "group" => $group]);
            throw new RuntimeException("action only allowed for admin: " . $action);
        }

        // if action is in the SECURE_ACTIONS, allow all
        if (self::SECURE_ACTIONS[$action]) {
            $this->logger->info("allow safe action", ["action" => $action, "group" => $group]);
            return;
        }

        // if action is restricted, only allow contributor
        if (self::SECURE_ACTIONS[$action] === false && in_array($group, ["admin", "contributor"], true)) {
            $this->logger->info("contributor and admin are allowed", ["action" => $action, "group" => $group]);
            return;
        }

        $this->logger->info("not authorized", ["action" => $action, "group" => $group]);
        throw new RuntimeException("action not allowed: " . $action);
    }
}
#####################################

interface ServiceInterface
{
    public function getUser(string $id): ?User;
    public function insertUser(User $user): bool;
    public function updateUser(User $user): bool;

    public function getCategories(): array;
    public function insertCategory(Category $category): bool;
    
    public function getTypes(): array;
    public function insertType(Type $type): bool;

    public function getNode(string $id): ?Node;
    public function getNodes(): array;
    public function insertNode(Node $node): bool;
    public function updateNode(Node $node): bool;
    public function deleteNode(string $id): bool;

    public function getEdge(string $source, string $target): ?Edge;
    public function getEdges(): array;
    public function insertEdge(Edge $edge): bool;
    public function updateEdge(Edge $edge): bool;
    public function deleteEdge(string $source, string $target): bool;

    public function updateNodeStatus(Status $status): bool;

    public function getProject(string $id): ?Project;
    public function getProjects(): array;
    public function insertProject(Project $project): bool;
    public function updateProject(Project $project): bool;
    public function deleteProject(string $id): bool;

    public function getLogs(int $limit): array;
}
#####################################

final class ServiceException extends RuntimeException
{
    public function __construct(string $message, PDOException $exception)
    {
        $message = "Service Error: " . $message  .". Exception: ". $exception->getMessage();
        parent::__construct($message, 0, $exception);
    }
}

#####################################

final class HelperCytoscape
{
    private DatabaseInterface $database;

    private const KEYNAME_ELEMENTS = "elements";
    private const KEYNAME_STYLES = "style";
    private const KEYNAME_LAYOUT = "layout";
    private const KEYNAME_ZOOM = "zoom";
    private const KEYNAME_PAN = "pan";
    private const KEYNAME_PANX = "x";
    private const KEYNAME_PANY = "y";

    private string $imageBaseUrl = "";

    private array $categories;

    public function __construct(DatabaseInterface $database, string $imageBaseUrl)
    {
        $this->database = $database;
        $this->categories = $this->database->getCategories();
        $this->imageBaseUrl = $imageBaseUrl;
    }

    public function toArray(Graph $graph): array
    {
        return [
            self::KEYNAME_ELEMENTS => [
                'nodes' => $this->getNodes($graph),
                'edges' => $this->getEdges($graph),
            ],

            self::KEYNAME_STYLES => $this->getStyle(),

            self::KEYNAME_LAYOUT => $this->getLayout(),

            self::KEYNAME_ZOOM => 1,

            self::KEYNAME_PAN => [
                self::KEYNAME_PANX => 0,
                self::KEYNAME_PANY => 0,
            ],
        ];
    }

    private function getNodes(Graph $graph): array
    {
        $graphArr = $graph->toArray();
        $nodes = [];
        foreach ($graphArr['nodes'] as $index => $node) {
            $node = $node->toArray();
            $nodes[] = [
                "data" => array_merge([
                    'id' => $node['id'],
                    'label' => $node['label'],
                    'category' => $node['category'],
                    'type' => $node['type'],
                ], $node["data"]),
                "classes" => [
                    "node-category-".$node['category'],
                    "node-type-".$node['type'],
                    "node-status-unknown",
                ],
            ];
        }

        return $nodes;
    }

    private function getEdges(Graph $graph): array
    {
        $edgesArr = $graph->toArray();
        $edges = [];
        foreach ($edgesArr['edges'] as $edge) {
            $edge = $edge->toArray();
            $edges[] = [
                "data" => [
                    'id'     => $edge['id'],
                    'source' => $edge['source'],
                    'target' => $edge['target'],
                    'label'  => $edge['label'],
                    'data'   => $edge['data'],
                ]
            ];
        }
        return $edges;
    }

    private function getStyle(): array
    {
        $baseStyle = [
            [
                "selector" => "node",
                "style" => [
                    "background-clip" => "none",
                    "background-height" => "32px",
                    "background-width" => "32px",
                    "border-width" => 2,
                    "color" => "#333",
                    "font-family" => "Tahoma, Geneva, Verdana, sans-serif",
                    "font-size" => 16,
                    "label" => "data(label)",
                    "text-valign" => "bottom",
                    "text-halign" => "center",
                    "text-margin-y" => 8,
                ],
            ],
            [
                "selector" => "edge",
                "style" => [
                    "color" => "#333",
                    "font-family" => "Tahoma, Geneva, Verdana, sans-serif",
                    "font-size" => 14,
                    //"label" => "data(label)",
                    "line-color" => "#bebebe",
                    "target-arrow-color" => "#7d7d7d",
                    "target-arrow-shape" => "triangle",
                    "target-arrow-fill" => "filled",
                    "target-arrow-width" => 6,
                    "target-endpoint" => "outside-to-node-or-label",
                    "target-distance-from-node" => 5,
                    "text-valign" => "bottom",
                    "text-halign" => "center",
                    "text-margin-y" => 10,
                    "width" => 3,
                    'curve-style' => 'bezier',

                    "line-style" => 'dashed',
                    'line-dash-pattern'  => [6, 3],
                    'line-dash-offset' => 0,
                    'transition-property' => 'line-dash-offset',
                    'transition-duration' => '1000ms',
                    'transition-timing-function' => 'linear'
                ],
            ],
            [
                "selector" => "edge:selected",
                "style" => [
                    "line-color" => "#00ff00",
                    "width" => 5,
                ],
            ]
        ];

        $nodeStyles = $this->getNodeStyles();

        return array_merge($baseStyle, $nodeStyles);
    }

    private function getNodeStyles(): array
    {
        $style = [];

        $style[] = [
            "selector" => "node.node-status-unknown",
            "style" => [
                "border-color" => "#939393",
                "background-color" => "#cbcbcb",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-healthy",
            "style" => [
                "border-color" => "#4CAF50",
                "background-color" => "#d0edd1",
                "color" => "#000000",
            ],
        ];

        $style[] = [
            "selector" => "node.node-status-unhealthy",
            "style" => [
                "border-color" => "#ff8178",
                "background-color" => "#ffe2e2",
                "color" => "#000000",
            ],
        ];
        
        $style[] = [
            "selector" => "node.node-status-maintenance",
            "style" => [
                "border-color" => "#43aeff",
                "background-color" => "#cde9ff",
                "color" => "#000000",
            ],
        ];

        $style[] = [
            "selector" => "node.node-status-impacted",
            "style" => [
                "border-color" => "#ae6ec0",
                "background-color" => "#ece5ee",
                "color" => "#000000",
            ],
        ];

        // $types = $this->imagesHelper->getTypes();
        // foreach($types as $type) {
        //     $style[] = [
        //         "selector" => "node.node-type-{$type}",
        //         "style" => [
        //             "background-image" => "{$this->imageBaseUrl}?img={$type}",
        //         ],
        //     ];
        // }
        
        foreach($this->categories as $category) {
            $style[] = [
                "selector" => "node.node-category-" . $category->id,
                "style" => [
                    "shape" => $category->shape,
                    "width" => $category->width,
                    "height" => $category->height,
                ],
            ];
        }

        $style[] = [
            "selector" => "node:active",
            "style" => [
                "border-width" => 4,
                "border-color" => "#ffec7f",

                "overlay-color" => "#FFF",
                "overlay-opacity" => 0,

                "outline-width"   => "5",
                "outline-style"   => "solid",
                "outline-color"   => "rgb(255, 255, 229)",
                "outline-opacity" => "1",
                "outline-offset"  => "5",
            ],
        ];
        
        $style[] = [
            "selector" => "node:selected",
            "style" => [
                "border-width" => 4,
                "border-color" => "#ffe658",
            ],
        ];

        return $style;
    }

    private function getLayout(): array
    {
        return [
            "name"              => "breadthfirst",
            "fit"               => true,
            "directed"          => true,
            "direction"         => "downward",
            "padding"           => 100,
            "avoidOverlap"      => true,
            "animate"           => false,
            //"animationDuration" => 500,
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

interface ResponseInterface
{
    public const KEYNAME_CODE    = "code";
    public const KEYNAME_STATUS  = "status";
    public const KEYNAME_HEADERS = "headers";
    public const KEYNAME_MESSAGE = "message";
    public const KEYNAME_DATA    = "data";

    public const VALUE_STATUS_SUCCESS = "success";
    public const VALUE_STATUS_ERROR   = "error";

    public const JSON_RESPONSE_CONTENT_TYPE = "Content-Type: application/json; charset=utf-8";
}

#####################################

class OKResponse extends Response
{
    public function __construct(string $message, array $data)
    {
        return parent::__construct(200, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}

#####################################

class NoContentResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(204, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}
#####################################

class BadRequestResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(400, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}
#####################################

class MethodNotAllowedResponse extends Response
{
    public function __construct(string $method, string $classMethod)
    {
        return parent::__construct(405, ResponseInterface::VALUE_STATUS_ERROR, "method '{$method}' not allowed in '{$classMethod}'", ['method' => $method, 'location' => $classMethod]);
    }
}
#####################################

class ForbiddenResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(403, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}
#####################################

class NotFoundResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(404, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}
#####################################

class UnauthorizedResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(401, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}
#####################################

class InternalServerErrorResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(500, ResponseInterface::VALUE_STATUS_ERROR, $message, $data);
    }
}
#####################################

class CreatedResponse extends Response
{
    public function __construct(string $message = "", array $data)
    {
        return parent::__construct(201, ResponseInterface::VALUE_STATUS_SUCCESS, $message, $data);
    }
}

#####################################

final class RequestException extends RuntimeException
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

final class Request
{
    public array $data;
    public array $params;
    public string $path;
    public string $method;
    public string $basePath;

    public const METHOD_GET    = "GET";
    public const METHOD_POST   = "POST";
    public const METHOD_PUT    = "PUT";
    public const METHOD_DELETE = "DELETE";

    public function __construct()
    {
        $this->params = $_GET;
        $this->method = $_SERVER["REQUEST_METHOD"];

        $requestUri = $_SERVER["REQUEST_URI"];
        $requestUri = strtok($requestUri, "?");

        $this->basePath = rtrim(dirname($requestUri), "/\\");
        
        $scriptName = $_SERVER["SCRIPT_NAME"];
        
        $this->path = str_replace($scriptName, "", $requestUri);
        
        if ($this->method === self::METHOD_POST || $this->method === self::METHOD_PUT || $this->method === self::METHOD_DELETE) {
            $jsonData = file_get_contents("php://input");
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
        throw new RequestException("param '{$name}' is missing", [], $this->params, $this->path);
    }
}
#####################################

interface ControllerInterface
{
    public function getUser(Request $req): ResponseInterface;
    public function insertUser(Request $req): ResponseInterface;
    public function updateUser(Request $req): ResponseInterface;

    public function getCategories(Request $req): ResponseInterface;
    public function getTypes(Request $req): ResponseInterface;

    public function getNode(Request $req): ResponseInterface;
    public function getNodes(Request $req): ResponseInterface;
    public function insertNode(Request $req): ResponseInterface;
    public function updateNode(Request $req): ResponseInterface;
    public function deleteNode(Request $req): ResponseInterface;

    public function getEdge(Request $req): ResponseInterface;
    public function getEdges(Request $req): ResponseInterface;
    public function insertEdge(Request $req): ResponseInterface;
    public function updateEdge(Request $req): ResponseInterface;
    public function deleteEdge(Request $req): ResponseInterface;

    public function updateNodeStatus(Request $req): ResponseInterface;

    public function getProject(Request $req): ResponseInterface;
    public function getProjects(Request $req): ResponseInterface;
    public function insertProject(Request $req): ResponseInterface;
    public function updateProject(Request $req): ResponseInterface;
    public function deleteProject(Request $req): ResponseInterface;
    public function getLogs(Request $req): ResponseInterface;
}
#####################################

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

        $id = $this->createSlug($req->data['name'] ?? 'project');

        $project = new Project(
            $id,
            $req->data['name'],
            $creator,
            $now,
            $now,
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

#####################################

final class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $group,
    ) {
    }
}

#####################################

final class NodeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $category,
        public readonly string $type,
        public readonly array $data
    ) {
    }
}
#####################################

// Graph DTO for representing nodes and edges in a graph structure.
final class GraphDTO
{
    public function __construct(
        public readonly array $nodes,
        public readonly array $edges
    ) {
    }
}
#####################################

// Edge DTO join two nodes in a graph structure.
// It contains identifiers for the source and target nodes, a label for the edge, and optional metadata.
final class EdgeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $source,
        public readonly string $target,
        public readonly string $label,
        public readonly array $data = []
    ) {
    }
}
#####################################

// The category of a Node.
// The category influences the shape and size of the Node when rendered.
// It is modeled as a DTO because new categories may be added in the future.
final class CategoryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $shape,
        public readonly int    $width,
        public readonly int    $height
    ) {
    }
}
#####################################

final class ProjectDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $author,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
        public readonly array $data,
    ) {
    }
}
#####################################

// The type of a Node.
// The type influences the icon displayed for the Node.
// It is modeled as a DTO because new types may be added in the future.
final class TypeDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }
}
#####################################

final class LogDTO
{
    public function __construct(
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $action,
        public readonly ?array $oldData,
        public readonly ?array $newData,
        public readonly string $userId,
        public readonly string $ipAddress,
        public readonly DateTimeImmutable $timestamp,
    ) {
    }
}
#####################################

final class StatusDTO
{
    public function __construct(
        public readonly string $node_id,
        public readonly ?string $status
    ) {
    }
}
#####################################

