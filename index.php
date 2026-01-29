<?php

declare(strict_types=1);

require_once __DIR__ . '/compiled/compiled_schema.php';
require_once __DIR__ . '/compiled/graph.php';

$username = 'admin';
$usergroup = 'admin';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$templateDir = __DIR__ . '/templates/';

HelperContext::update($username, $usergroup, $ip);

$pdo              = Database::createConnection('sqlite:database.sqlite');
$logger           = new Logger(1);
$database         = new Database($pdo, $logger, $SQL_SCHEMA);
$service          = new Service($database, $logger);
$cytoscapeHelper  = new HelperCytoscape($database, '/index.php/getImage');
$controller       = new Controller($service, $cytoscapeHelper, $logger);
$router           = new RequestRouter($controller);
$renderer         = new Renderer($templateDir);

if($_SERVER['REQUEST_URI'] === '/favicon.ico') {
    return false;
}

if(str_contains($_SERVER['REQUEST_URI'], 'images')) {
    return false;
}

$request    = new Request();
$response   = $router->handle($request);
$renderer->render($response);

throw new Exception('unreachable code reached?');
