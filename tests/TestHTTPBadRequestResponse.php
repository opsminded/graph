<?php

declare(strict_types=1);

class TestHTTPBadRequestResponse extends TestAbstractTest
{
    public function testHTTPBadRequestResponse(): void
    {
        $resp = new HTTPBadRequestResponse('bad request', ['key' => 'val']);
        if($resp->code != 400) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "bad request") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}