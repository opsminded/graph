<?php

declare(strict_types=1);

require_once __DIR__ . '/graph.php';

include __DIR__ . '/www/images/compiled_images.php';

$imageHelper = new HelperImages($images);

$username = 'admin';
$usergroup = 'admin';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

HelperContext::update($username, $usergroup, $ip);

$pdo              = Database::createConnection('sqlite:database.sqlite');
$pdo              = Database::createConnection('sqlite::memory:');
$databaseLogger   = new Logger();
$serviceLogger    = new Logger();
$controllerLogger = new Logger();

$database   = new Database($pdo, $databaseLogger);
$service    = new Service($database, $serviceLogger);

$service->insertNode(new ModelNode('Credito', 'Crédito', 'business', 'business', ['host' => 'users.example.com']));
$service->insertNode(new ModelNode('Pagamento', 'Pagamento', 'business', 'business_case', ['host' => 'payments.example.com']));
$service->insertNode(new ModelNode('UserService', 'User Service', 'application', 'service', ['host' => 'users.example.com']));
$service->insertNode(new ModelNode('AuthService', 'Authentication Service', 'application', 'service', ['host' => 'auth.example.com']));
$service->insertNode(new ModelNode('UserDatabase', 'User Database', 'infrastructure', 'database', ['host' => 'users.example.com']));
$service->insertEdge(new ModelEdge('UserService', 'AuthService', ['method' => 'OAuth2']));
$service->insertEdge(new ModelEdge('UserService', 'UserDatabase', ['method' => 'SQL']));
$service->updateNodeStatus(new ModelStatus('Credito', ModelStatus::STATUS_VALUE_UNKNOWN));
$service->updateNodeStatus(new ModelStatus('Pagamento', ModelStatus::STATUS_VALUE_HEALTHY));
$service->updateNodeStatus(new ModelStatus('UserService', ModelStatus::STATUS_VALUE_IMPACTED));
$service->updateNodeStatus(new ModelStatus('AuthService', ModelStatus::STATUS_VALUE_UNHEALTHY));
$service->updateNodeStatus(new ModelStatus('UserDatabase', ModelStatus::STATUS_VALUE_MAINTENANCE));

$service->insertNode(new ModelNode('BaseServer', 'Base Server', 'infrastructure', 'server', ['host' => 'users.example.com']));
$service->updateNodeStatus(new ModelStatus('BaseServer', ModelStatus::STATUS_VALUE_HEALTHY));

$imageHelper = new HelperImages($images);
$cytoscapeHelper = new HelperCytoscape($database, $imageHelper, '/image.php');

$controller = new HTTPController($service, $cytoscapeHelper, $controllerLogger);

$router = new HTTPRequestRouter($controller);

$req = new HTTPRequest();
$resp = $router->handle($req);
$resp->send();