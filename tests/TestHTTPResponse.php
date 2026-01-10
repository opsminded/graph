<?php

declare(strict_types=1);

class TestHTTPResponse extends TestAbstractTest
{
    public function testHTTPResponse(): void
    {
        $resp = new HTTPResponse(200, 'success', 'node created', ['key' => 'val']);
        if($resp->code != 200) {
            throw new Exception('problem on testHTTPResponse');
        }

        ob_start();
        $resp->send();
        $content = ob_get_clean();

        $data = json_decode($content, true);

        if ($data['code'] != 200) {
            throw new Exception('problem on testHTTPResponse');
        }
    }
}