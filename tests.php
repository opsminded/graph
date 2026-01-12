<?php

declare(strict_types=1);

if (! defined('XDEBUG_CC_UNUSED')) {
    define('XDEBUG_CC_UNUSED', 1);
}

if (! defined('XDEBUG_CC_DEAD_CODE')) {
    define('XDEBUG_CC_DEAD_CODE', 1);
}

if (! function_exists('xdebug_start_code_coverage')) {
    function xdebug_start_code_coverage() {}
}

if (! function_exists('xdebug_get_code_coverage')) {
    function xdebug_get_code_coverage() {}
}

if (! function_exists('xdebug_stop_code_coverage')) {
    function xdebug_stop_code_coverage() {}
}


ini_set('xdebug.mode', '1');
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

if (file_exists('database.log')) {
    @unlink('database.log');
}

if (file_exists('service.log')) {
    @unlink('service.log');
}

if (file_exists('controller.log')) {
    @unlink('controller.log');
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

require_once __DIR__ . '/src/GraphDatabaseInterface.php';
require_once __DIR__ . '/src/GraphServiceInterface.php';
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

$tests = [
    new TestGraphDatabase(),

    new TestGraphService(),

    new TestHelperGraphContext(),
    
    new TestHelperLogger(),

    new TestHTTPController(),
    new TestHTTPOKResponse(),
    new TestHTTPBadRequestResponse(),
    new TestHTTPCreatedResponse(),
    new TestHTTPNotFoundResponse(),

    new TestHTTPRequest(),
    new TestHTTPRequestException(),

    new TestHTTPResponse(),
    
    new TestModelEdge(),
    new TestModelGraph(),
    new TestModelGroup(),
    new TestModelLog(),
    new TestModelNode(),
    new TestModelStatus(),
    new TestModelUser(),
];

foreach($tests as $test)
{
    $test->run();
}


///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

// if (file_exists('database.log')) {
//     @unlink('database.log');
// }

// if (file_exists('service.log')) {
//     @unlink('service.log');
// }

// if (file_exists('controller.log')) {
//     @unlink('controller.log');
// }

echo "fim\n";
