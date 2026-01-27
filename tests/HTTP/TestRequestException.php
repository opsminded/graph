<?php

declare(strict_types=1);

class TestRequestException extends TestAbstractTest
{
    public function testRequestException(): void
    {
        $req = new RequestException('message', ['key' => 'val'], ['id' => ''], '/get');
        if($req->getData() != ['key' => 'val']) {
            throw new Exception('problem on TestRequestException');
        }

        if($req->getParams() != ['id' => '']) {
            throw new Exception('problem on TestRequestException');
        }

        if($req->getPath() != '/get') {
            throw new Exception('problem on TestRequestException');
        }
    }
}