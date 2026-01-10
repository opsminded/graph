<?php

declare(strict_types=1);

class TestHTTPNotFoundResponse extends TestAbstractTest
{
    public function testHTTPNotFoundResponse(): void
    {
        $resp = new HTTPNotFoundResponse('node not found', ['key' => 'val']);
        if($resp->code != 404) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node not found") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}