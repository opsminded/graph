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

///////////////////////////////////////////////////////////////////////////////////////////////////

require_once dirname(__DIR__) . '/compiled/graph.php';
require_once dirname(__DIR__) . '/compiled/tests.php';

$tests = [
    new TestDatabase(),
    new TestService(),
    new TestHelperContext(),
    new TestHelperLogger(),
    new TestHelperCytoscape(),
    new TestController(),
    new TestOKResponse(),
    new TestBadRequestResponse(),
    new TestCreatedResponse(),
    new TestForbiddenResponse(),
    new TestInternalServerErrorResponse(),
    new TestNotFoundResponse(),
    new TestRequest(),
    new TestRequestException(),
    new TestRequestRouter(),
    new TestResponse(),
    new TestUnauthorizedResponse(),
    new TestEdge(),
    new TestGraph(),
    new TestGroup(),
    new TestLog(),
    new TestNode(),
    new TestStatus(),
    new TestUser(),
];

foreach($tests as $test)
{
    $test->run();
}

///////////////////////////////////////////////////////////////////////////////////////////////////

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('/tmp/coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

echo "fim\n";
