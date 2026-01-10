<?php

declare(strict_types=1);

class TestHTTPRequestException extends TestAbstractTest
{
    public function testHTTPRequestException(): void
    {
        $req = new HTTPRequestException('message', ['key' => 'val'], ['id' => ''], '/get');
        if($req->getData() != ['key' => 'val']) {
            throw new Exception('problem on TestHTTPRequestException');
        }

        if($req->getParams() != ['id' => '']) {
            throw new Exception('problem on TestHTTPRequestException');
        }

        if($req->getPath() != '/get') {
            throw new Exception('problem on TestHTTPRequestException');
        }
    }
}