<?php

declare(strict_types=1);

class TestHTTPCreatedResponse extends TestAbstractTest
{
    public function testHTTPCreatedResponse(): void
    {
        $resp = new HTTPCreatedResponse('node created', ['key' => 'val']);
        if($resp->code != 201) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}