<?php

declare(strict_types=1);

final class HTTPRenderer
{
    private array $templates;
    
    public function __construct(array $templates)
    {
        $this->templates = $templates;
    }

    public function render(HTTPResponse $response): void
    {
        header("Access-Control-Allow-Origin: *");
        http_response_code($response->code);

        foreach ($response->headers as $header => $value) {
            header("$header: $value");
        }

        if ($response->binaryContent !== null) {
            header($response->contentType);
            echo base64_decode($response->binaryContent);
            exit();
        }

        if($response->template === null) {
            header('Content-Type: application/json; charset=utf-8');
            $data = [
                HTTPResponseInterface::KEYNAME_CODE => $response->code,
                HTTPResponseInterface::KEYNAME_STATUS => $response->status,
                HTTPResponseInterface::KEYNAME_MESSAGE => $response->message,
                HTTPResponseInterface::KEYNAME_DATA => $response->data
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
            exit();
        } else {
            header('Content-Type: text/html; charset=utf-8');
            if (!array_key_exists($response->template, $this->templates)) {
                throw new Exception("template not found: " . $response->template);
            }
            $content = base64_decode($this->templates[$response->template]['data']);
            eval('?>' . $content);
            exit();
        }
    }
}