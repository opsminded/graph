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

require_once __DIR__ . '/tests/TestEdge.php';
require_once __DIR__ . '/tests/TestGraph.php';
require_once __DIR__ . '/tests/TestGraphContext.php';
require_once __DIR__ . '/tests/TestGraphDatabase.php';
require_once __DIR__ . '/tests/TestGraphService.php';
require_once __DIR__ . '/tests/TestGroup.php';
require_once __DIR__ . '/tests/TestLog.php';
require_once __DIR__ . '/tests/TestLogger.php';
require_once __DIR__ . '/tests/TestNode.php';
require_once __DIR__ . '/tests/TestSecurityException.php';
require_once __DIR__ . '/tests/TestStatus.php';
require_once __DIR__ . '/tests/TestUser.php';

$tests = [
    new TestEdge(),
    new TestGraph(),
    new TestGraphContext(),
    new TestGraphDatabase(),
    new TestGraphService(),
    new TestGroup(),
    new TestLog(),
    new TestLogger(),
    new TestNode(),
    new TestSecurityException(),
    new TestStatus(),
    new TestUser(),
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
