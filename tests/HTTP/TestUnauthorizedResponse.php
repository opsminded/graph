<?php

declare(strict_types=1);

class TestUnauthorizedResponse extends TestAbstractTest
{
    public function testUnauthorizedResponse(): void
    {
        $resp = new UnauthorizedResponse('database error', ['key' => 'val']);
        if($resp->code != 401) {
            throw new Exception('problem on code testUnauthorizedResponse 1');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testUnauthorizedResponse 2');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on testUnauthorizedResponse 3');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testUnauthorizedResponse 4');
        }
    }
}