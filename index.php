<?php

declare(strict_types=1);

if (file_exists('database.log')) {
    @unlink('database.log');
}

if (file_exists('service.log')) {
    @unlink('service.log');
}

if (file_exists('controller.log')) {
    @unlink('controller.log');
}

require_once __DIR__ . '/src/DatabaseInterface.php';
require_once __DIR__ . '/src/ServiceInterface.php';
require_once __DIR__ . '/src/LoggerInterface.php';
require_once __DIR__ . '/src/HTTPControllerInterface.php';
require_once __DIR__ . '/src/HTTPResponseInterface.php';
require_once __DIR__ . '/src/HTTPResponse.php';

foreach (glob(__DIR__ . "/src/*.php") as $arquivo) {
    require_once $arquivo;
}

foreach (glob(__DIR__ . "/tests/*.php") as $arquivo) {
    require_once $arquivo;
}

$pdo = Database::createConnection('sqlite::memory:');
$databaseLogger   = new Logger('database.log');
$serviceLogger    = new Logger('service.log');
$controllerLogger = new Logger('controller.log');

$database = new Database($pdo, $databaseLogger);
$service = new Service($database, $serviceLogger);
$controller = new HTTPController($service, $serviceLogger);

$router = new HTTPRequestRouter($controller);

HelperContext::update('admin', 'admin', '127.0.0.1');

$request = new HTTPRequest();
$response = $router->handle($request);
$response->send();
