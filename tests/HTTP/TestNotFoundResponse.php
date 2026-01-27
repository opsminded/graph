<?php

declare(strict_types=1);

class TestNotFoundResponse extends TestAbstractTest
{
    public function testNotFoundResponse(): void
    {
        $resp = new NotFoundResponse('node not found', ['key' => 'val']);
        if($resp->code != 404) {
            throw new Exception('problem on code testNotFoundResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status testNotFoundResponse');
        }

        if ($resp->message != "node not found") {
            throw new Exception('problem on testNotFoundResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testNotFoundResponse');
        }
    }
}