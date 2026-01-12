<?php

declare(strict_types=1);

class TestHTTPUnauthorizedResponse extends TestAbstractTest
{
    public function testHTTPUnauthorizedResponse(): void
    {
        $resp = new HTTPUnauthorizedResponse('database error', ['key' => 'val']);
        if($resp->code != 401) {
            throw new Exception('problem on code testHTTPUnauthorizedResponse 1');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testHTTPUnauthorizedResponse 2');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on testHTTPUnauthorizedResponse 3');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPUnauthorizedResponse 4');
        }
    }
}