<?php

declare(strict_types=1);

final class Renderer
{
    private string $templateDir;
    
    public function __construct(string $templateDir)
    {
        $templateDir = rtrim($templateDir, '/');
        $this->templateDir = $templateDir;
    }

    public function render(Response $response): void
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
                ResponseInterface::KEYNAME_CODE => $response->code,
                ResponseInterface::KEYNAME_STATUS => $response->status,
                ResponseInterface::KEYNAME_MESSAGE => $response->message,
                ResponseInterface::KEYNAME_DATA => $response->data
            ];
            echo json_encode($data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
            exit();
        } else {
            header('Content-Type: text/html; charset=utf-8');
            $templatePath = $this->templateDir . '/' . $response->template;
            if (!file_exists($templatePath)) {
                echo $templatePath;
                exit();
                throw new Exception("template not found: " . $response->template);
            }

            ob_start();
            include $templatePath;
            $content = ob_get_clean();
            echo $content;
            exit();
        }
    }
}