<?php

declare(strict_types=1);

class HTTPResponse implements HTTPResponseInterface
{
    public int $code;
    public string $status;
    public string $message;
    public array $data;
    public array $headers;
    public ?string $template;

    public function __construct(int $code, string $status, string $message = "", array $data, array $headers = [], ?string $template = null)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
        $this->headers = $headers;
        $this->template = $template;
    }

    // public function send(): void
    // {
    //     header(HTTPResponseInterface::JSON_RESPONSE_CONTENT_TYPE);
    //     http_response_code($this->code);
    //     $this->data = [HTTPResponseInterface::KEYNAME_CODE => $this->code, HTTPResponseInterface::KEYNAME_STATUS => $this->status, HTTPResponseInterface::KEYNAME_MESSAGE => $this->message, HTTPResponseInterface::KEYNAME_DATA => $this->data];
    //     echo json_encode($this->data, JSON_UNESCAPED_UNICODE |  JSON_UNESCAPED_SLASHES |  JSON_PRETTY_PRINT);
    // }
}