<?php

declare(strict_types=1);

final class TestHTTPOKResponse extends TestAbstractTest
{
    public function testHTTPOKResponse(): void
    {
        $resp = new HTTPOKResponse('node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testHTTPOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testHTTPOKResponse');
        }
    }
}