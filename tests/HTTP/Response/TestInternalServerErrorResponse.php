<?php

declare(strict_types=1);

class TestInternalServerErrorResponse extends TestAbstractTest
{
    public function testInternalServerErrorResponse(): void
    {
        $resp = new InternalServerErrorResponse('database error', ['key' => 'val']);
        if($resp->code != 500) {
            throw new Exception('problem on code TestInternalServerErrorResponse');
        }

        if ($resp->status != "error") {
            throw new Exception('problem on status TestInternalServerErrorResponse');
        }

        if ($resp->message != "database error") {
            throw new Exception('problem on TestInternalServerErrorResponse');
        }

        if ($resp->data != ['key' => 'val']) {
            throw new Exception('problem on TestInternalServerErrorResponse');
        }
    }
}