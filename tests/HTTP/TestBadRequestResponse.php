<?php

declare(strict_types=1);

class TestBadRequestResponse extends TestAbstractTest
{
    public function testBadRequestResponse(): void
    {
        $resp = new BadRequestResponse('bad request', ['key' => 'val']);
        if($resp->code != 400) {
            throw new Exception('problem on code testBadRequestResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testBadRequestResponse');
        }

        if ($resp->message != "bad request") {
            throw new Exception('problem on testBadRequestResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testBadRequestResponse');
        }
    }
}