<?php

declare(strict_types=1);

require_once __DIR__ . 'graph.php';

include __DIR__ . '/www/images/compiled_images.php';

foreach (glob(__DIR__ . "/tests/*.php") as $arquivo) {
    require_once $arquivo;
}

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

$imageHelper = new HelperImages($images);
$cytoscapeHelper = new HelperCytoscape($database, $imageHelper);

$controller = new HTTPController($service, $cytoscapeHelper, $serviceLogger);
$router     = new HTTPRequestRouter($controller);

$request    = new HTTPRequest();
$response   = $router->handle($request);
$response->send();
