<?php

declare(strict_types=1);

class TestRequest extends TestAbstractTest
{
    public function testRequest(): void
    {
        $_GET['id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new Request();
        if($req->getParam('id') != 1) {
            throw new Exception('problem on testRequest');
        }
    }
}