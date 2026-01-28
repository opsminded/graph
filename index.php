<?php

declare(strict_types=1);

require_once __DIR__ . '/compiled/graph.php';

$username = 'admin';
$usergroup = 'admin';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

HelperContext::update($username, $usergroup, $ip);

$pdo              = Database::createConnection('sqlite:database.sqlite');
//$pdo              = Database::createConnection('sqlite::memory:');
$logger           = new Logger(1);
$database         = new Database($pdo, $logger, $SQL_SCHEMA);
$service          = new Service($database, $logger);
$imageHelper      = new HelperImages($DATA_IMAGES);
$cytoscapeHelper  = new HelperCytoscape($database, $imageHelper, '/index.php/getImage');
$controller       = new Controller($service, $cytoscapeHelper, $logger);
$router           = new RequestRouter($controller);
$renderer         = new Renderer($DATA_TEMPLATES);

if(false) {
    $service->insertNode(new Node('Credito', 'Crédito', 'business', 'business', false, ['host' => 'users.example.com', 'api' => 'http://users.example.com']));
    $service->insertNode(new Node('Pagamento', 'Pagamento', 'business', 'business_case', false, ['host' => 'payments.example.com']));
    $service->insertNode(new Node('UserService', 'User Service', 'application', 'service', false, ['host' => 'users.example.com']));
    $service->insertNode(new Node('AuthService', 'Authentication Service', 'application', 'service', false, ['host' => 'auth.example.com']));
    $service->insertNode(new Node('UserDatabase', 'User Database', 'infrastructure', 'database', false, ['host' => 'users.example.com']));
    $service->insertEdge(new Edge('Credito', 'Pagamento', 'Solicita'));
    $service->insertEdge(new Edge('Pagamento', 'UserService', 'Solicita'));
    $service->insertEdge(new Edge('UserService', 'AuthService', 'Solicita', ['method' => 'OAuth2']));
    $service->insertEdge(new Edge('UserService', 'UserDatabase', 'Solicita', ['method' => 'SQL']));
    $service->updateNodeStatus(new Status('Credito', Status::STATUS_VALUE_UNKNOWN));
    $service->updateNodeStatus(new Status('Pagamento', Status::STATUS_VALUE_HEALTHY));
    $service->updateNodeStatus(new Status('UserService', Status::STATUS_VALUE_IMPACTED));
    $service->updateNodeStatus(new Status('AuthService', Status::STATUS_VALUE_UNHEALTHY));
    $service->updateNodeStatus(new Status('UserDatabase', Status::STATUS_VALUE_MAINTENANCE));

    $service->insertNode(new Node('Credito2', 'Crédito', 'business', 'business', false, ['host' => 'users.example.com']));
    $service->insertNode(new Node('Pagamento2', 'Pagamento', 'business', 'business_case', false, ['host' => 'payments.example.com']));
    $service->insertNode(new Node('UserService2', 'User Service', 'application', 'service', false, ['host' => 'users.example.com']));
    $service->insertNode(new Node('AuthService2', 'Authentication Service', 'application', 'service', false, ['host' => 'auth.example.com']));
    $service->insertNode(new Node('UserDatabase2', 'User Database', 'infrastructure', 'database', false, ['host' => 'users.example.com']));
    $service->insertEdge(new Edge('Credito2', 'Pagamento2', 'Solicita'));
    $service->insertEdge(new Edge('Pagamento2', 'UserService2', 'Solicita'));
    $service->insertEdge(new Edge('UserService2', 'AuthService2', 'Solicita', ['method' => 'OAuth2']));
    $service->insertEdge(new Edge('UserService2', 'UserDatabase2', 'Solicita', ['method' => 'SQL']));
    $service->updateNodeStatus(new Status('Credito2', Status::STATUS_VALUE_UNKNOWN));
    $service->updateNodeStatus(new Status('Pagamento2', Status::STATUS_VALUE_HEALTHY));
    $service->updateNodeStatus(new Status('UserService2', Status::STATUS_VALUE_IMPACTED));
    $service->updateNodeStatus(new Status('AuthService2', Status::STATUS_VALUE_UNHEALTHY));
    $service->updateNodeStatus(new Status('UserDatabase2', Status::STATUS_VALUE_MAINTENANCE));

    $service->insertNode(new Node('BaseServer', 'Base Server', 'infrastructure', 'server', false, ['host' => 'users.example.com']));
    $service->insertEdge(new Edge('UserDatabase', 'BaseServer', 'Conecta', ['method' => 'SSH']));
    $service->insertEdge(new Edge('UserDatabase2', 'BaseServer', 'Conecta', ['method' => 'SSH']));

    $service->updateNodeStatus(new Status('BaseServer', Status::STATUS_VALUE_HEALTHY));

    $service->insertProject(new Project('first', 'Primeiro', 'admin', new DateTimeImmutable(), new DateTimeImmutable(), ['Credito']));

    $service->insertNode(new Node('Flutua', 'Flutua', 'infrastructure', 'server', false, ['host' => 'users.example.com']));
}

if (false) {
    $letter = 'a';
    for ($x = 1; $x <= 3000; $x++) {
        $service->insertNode(new Node($letter . 'Node' . $x, $letter . 'Node ' . $x, 'application', 'service', false, ['host' => $letter . 'node' . $x . '.example.com']));
        // if ($x > 1) {
        //     for ($y = 1; $y <= 2; $y++) {
        //         $targetNode = $letter . 'Node' . rand(1, $x - 1);
        //         if ($targetNode !== $letter . 'Node' . ($x - 1)) {
        //             $service->insertEdge(new Edge($letter . 'Node' . $x, $targetNode, 'ConnectsTo'));
        //         }
        //     }
        // }
        $statusValues = [
            Status::STATUS_VALUE_HEALTHY,
            Status::STATUS_VALUE_IMPACTED,
            Status::STATUS_VALUE_UNHEALTHY,
            Status::STATUS_VALUE_MAINTENANCE,
            Status::STATUS_VALUE_UNKNOWN
        ];
        $randomStatus = $statusValues[array_rand($statusValues)];
        $service->updateNodeStatus(new Status($letter . 'Node' . $x, $randomStatus));
    }
}

if($_SERVER['REQUEST_URI'] === '/favicon.ico') {
    return false;
}

$request    = new Request();
$response   = $router->handle($request);
$renderer->render($response);

throw new Exception('unreachable code reached?');
