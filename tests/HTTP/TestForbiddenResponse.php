<?php

declare(strict_types=1);

class TestForbiddenResponse extends TestAbstractTest
{
    public function testForbiddenResponse(): void
    {
        $resp = new ForbiddenResponse('node not created', ['key' => 'val']);
        if($resp->code != 403) {
            throw new Exception('problem on code testForbiddenResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testForbiddenResponse');
        }

        if ($resp->message != "node not created") {
            throw new Exception('problem on testForbiddenResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testForbiddenResponse');
        }
    }
}