<?php

declare(strict_types=1);

class TestCreatedResponse extends TestAbstractTest
{
    public function testCreatedResponse(): void
    {
        $resp = new CreatedResponse('node created', ['key' => 'val']);
        if($resp->code != 201) {
            throw new Exception('problem on code TestCreatedResponse');
        }

        if ($resp->status != "success") {
            throw new Exception('problem on status TestCreatedResponse');
        }

        if ($resp->message != "node created") {
            throw new Exception('problem on TestCreatedResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestCreatedResponse');
        }
    }
}