<?php

declare(strict_types=1);

class TestHTTPInternalServerErrorResponse extends TestAbstractTest
{
    public function testHTTPInternalServerErrorResponse(): void
    {
        $resp = new HTTPInternalServerErrorResponse('database error', ['key' => 'val']);
        if($resp->code != 500) {
            throw new Exception('problem on code TestHTTPInternalServerErrorResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status TestHTTPInternalServerErrorResponse');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on TestHTTPInternalServerErrorResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestHTTPInternalServerErrorResponse');
        }
    }
}