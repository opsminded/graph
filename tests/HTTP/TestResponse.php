<?php

declare(strict_types=1);

class TestResponse extends TestAbstractTest
{
    public function testResponse(): void
    {
        $resp = new Response(200, 'success', 'node created', ['key' => 'val'], 'text/plain', 'dGVzdGU=', ['Content-Type' => 'application/json'], 'template');
        if($resp->code != 200) {
            throw new Exception('problem on testResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testResponse');
        }

        if ($resp->data['key'] != 'val') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->contentType != 'text/plain') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->headers['Content-Type'] != 'application/json') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->binaryContent != 'dGVzdGU=') {
            throw new Exception('problem on testResponse');
        }

        if ($resp->template != 'template') {
            throw new Exception('problem on testResponse');
        }   
    }
}