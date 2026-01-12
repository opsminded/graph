<?php

class TestHTTPRequest extends TestAbstractTest
{
    public function testHTTPRequest()
    {
        $_GET['id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = 'api.php';
        $_SERVER['REQUEST_URI'] = 'api.php/getNodes';
        $req = new HTTPRequest();
        if($req->getParam('id') != 1) {
            throw new Exception('problem on testHTTPRequest');
        }
    }
}