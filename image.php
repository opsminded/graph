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
$databaseLogger   = new Logger();
$serviceLogger    = new Logger();
$controllerLogger = new Logger();

$database   = new Database($pdo, $databaseLogger);
$service    = new Service($database, $serviceLogger);

// $service->insertNode(new ModelNode('UserService', 'User Service', 'application', 'service', ['host' => 'users.example.com']));
// $service->insertNode(new ModelNode('AuthService', 'Authentication Service', 'application', 'service', ['host' => 'auth.example.com']));
// $service->insertNode(new ModelNode('UserDatabase', 'User Database', 'infrastructure', 'database', ['host' => 'users.example.com']));
// $service->insertEdge(new ModelEdge('UserService', 'AuthService', ['method' => 'OAuth2']));
// $service->insertEdge(new ModelEdge('UserService', 'UserDatabase', ['method' => 'SQL']));

$imageHelper = new HelperImages($images);
$imageHelper->send($_GET['img']);
exit();