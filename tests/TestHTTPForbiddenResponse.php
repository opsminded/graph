<?php

declare(strict_types=1);

class TestHTTPForbiddenResponse extends TestAbstractTest
{
    public function testHTTPForbiddenResponse(): void
    {
        $resp = new HTTPForbiddenResponse('node not created', ['key' => 'val']);
        if($resp->code != 403) {
            throw new Exception('problem on code testHTTPOKResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPOKResponse');
        }

        if ($resp->message != "node not created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}