<?php

declare(strict_types=1);

class TestHTTPResponse extends TestAbstractTest
{
    public function testHTTPResponse(): void
    {
        $resp = new HTTPResponse(200, 'success', 'node created', ['key' => 'val'], 'text/plain', 'dGVzdGU=', ['Content-Type' => 'application/json'], 'template');
        if($resp->code != 200) {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->data['key'] != 'val') {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->contentType != 'text/plain') {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->headers['Content-Type'] != 'application/json') {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->binaryContent != 'dGVzdGU=') {
            throw new Exception('problem on testHTTPResponse');
        }

        if ($resp->template != 'template') {
            throw new Exception('problem on testHTTPResponse');
        }   
    }
}