<?php

declare(strict_types=1);

class HTTPResponse implements HTTPResponseInterface
{
    public int $code;
    public string $status;
    public string $message;
    public array $data;

    public function __construct(int $code, string $status, string $message = '', array $data)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
    }

    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->code);
        $this->data = ['code' => $this->code, 'status' => $this->status, 'message' => $this->message, 'data' => $this->data];
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
    }
}