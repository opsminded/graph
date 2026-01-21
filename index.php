<?php

declare(strict_types=1);

require_once __DIR__ . '/compiled/graph.php';

$username = 'admin';
$usergroup = 'admin';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

HelperContext::update($username, $usergroup, $ip);

$pdo              = Database::createConnection('sqlite:database.sqlite');
//$pdo              = Database::createConnection('sqlite::memory:');
$logger           = new Logger();
$controllerLogger = new Logger();
$database         = new Database($pdo, $logger);
$service          = new Service($database, $logger);
$imageHelper      = new HelperImages($DATA_IMAGES);
$cytoscapeHelper  = new HelperCytoscape($database, $imageHelper, '/index.php/getImage');
$controller       = new HttpController($service, $cytoscapeHelper, $logger);
$router           = new HTTPRequestRouter($controller);
$renderer         = new HTTPRenderer($DATA_TEMPLATES);

if(false) {
    $service->insertNode(new ModelNode('Credito', 'Crédito', 'business', 'business', false, ['host' => 'users.example.com', 'api' => 'http://users.example.com']));
    $service->insertNode(new ModelNode('Pagamento', 'Pagamento', 'business', 'business_case', false, ['host' => 'payments.example.com']));
    $service->insertNode(new ModelNode('UserService', 'User Service', 'application', 'service', false, ['host' => 'users.example.com']));
    $service->insertNode(new ModelNode('AuthService', 'Authentication Service', 'application', 'service', false, ['host' => 'auth.example.com']));
    $service->insertNode(new ModelNode('UserDatabase', 'User Database', 'infrastructure', 'database', false, ['host' => 'users.example.com']));
    $service->insertEdge(new ModelEdge('Credito', 'Pagamento', 'Solicita'));
    $service->insertEdge(new ModelEdge('Pagamento', 'UserService', 'Solicita'));
    $service->insertEdge(new ModelEdge('UserService', 'AuthService', 'Solicita', ['method' => 'OAuth2']));
    $service->insertEdge(new ModelEdge('UserService', 'UserDatabase', 'Solicita', ['method' => 'SQL']));
    $service->updateNodeStatus(new ModelStatus('Credito', ModelStatus::STATUS_VALUE_UNKNOWN));
    $service->updateNodeStatus(new ModelStatus('Pagamento', ModelStatus::STATUS_VALUE_HEALTHY));
    $service->updateNodeStatus(new ModelStatus('UserService', ModelStatus::STATUS_VALUE_IMPACTED));
    $service->updateNodeStatus(new ModelStatus('AuthService', ModelStatus::STATUS_VALUE_UNHEALTHY));
    $service->updateNodeStatus(new ModelStatus('UserDatabase', ModelStatus::STATUS_VALUE_MAINTENANCE));

    $service->insertNode(new ModelNode('Credito2', 'Crédito', 'business', 'business', false, ['host' => 'users.example.com']));
    $service->insertNode(new ModelNode('Pagamento2', 'Pagamento', 'business', 'business_case', false, ['host' => 'payments.example.com']));
    $service->insertNode(new ModelNode('UserService2', 'User Service', 'application', 'service', false, ['host' => 'users.example.com']));
    $service->insertNode(new ModelNode('AuthService2', 'Authentication Service', 'application', 'service', false, ['host' => 'auth.example.com']));
    $service->insertNode(new ModelNode('UserDatabase2', 'User Database', 'infrastructure', 'database', false, ['host' => 'users.example.com']));
    $service->insertEdge(new ModelEdge('Credito2', 'Pagamento2', 'Solicita'));
    $service->insertEdge(new ModelEdge('Pagamento2', 'UserService2', 'Solicita'));
    $service->insertEdge(new ModelEdge('UserService2', 'AuthService2', 'Solicita', ['method' => 'OAuth2']));
    $service->insertEdge(new ModelEdge('UserService2', 'UserDatabase2', 'Solicita', ['method' => 'SQL']));
    $service->updateNodeStatus(new ModelStatus('Credito2', ModelStatus::STATUS_VALUE_UNKNOWN));
    $service->updateNodeStatus(new ModelStatus('Pagamento2', ModelStatus::STATUS_VALUE_HEALTHY));
    $service->updateNodeStatus(new ModelStatus('UserService2', ModelStatus::STATUS_VALUE_IMPACTED));
    $service->updateNodeStatus(new ModelStatus('AuthService2', ModelStatus::STATUS_VALUE_UNHEALTHY));
    $service->updateNodeStatus(new ModelStatus('UserDatabase2', ModelStatus::STATUS_VALUE_MAINTENANCE));

    $service->insertNode(new ModelNode('BaseServer', 'Base Server', 'infrastructure', 'server', false, ['host' => 'users.example.com']));
    $service->insertEdge(new ModelEdge('UserDatabase', 'BaseServer', 'Conecta', ['method' => 'SSH']));
    $service->insertEdge(new ModelEdge('UserDatabase2', 'BaseServer', 'Conecta', ['method' => 'SSH']));

    $service->updateNodeStatus(new ModelStatus('BaseServer', ModelStatus::STATUS_VALUE_HEALTHY));

    $service->insertSave(new ModelSave('first', 'Primeiro', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['Credito']));

    $service->insertNode(new ModelNode('Flutua', 'Flutua', 'infrastructure', 'server', false, ['host' => 'users.example.com']));
}

if($_SERVER['REQUEST_URI'] === '/favicon.ico') {
    return false;
}

$request    = new HTTPRequest();
$response   = $router->handle($request);
$renderer->render($response);

throw new Exception('unreachable code reached?');
