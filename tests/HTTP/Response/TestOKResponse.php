<?php

declare(strict_types=1);

final class TestOKResponse extends TestAbstractTest
{
    public function testOKResponse(): void
    {
        $resp = new OKResponse('node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on testOKResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on testOKResponse');
        }
    }
}