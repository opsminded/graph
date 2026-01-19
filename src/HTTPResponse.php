<?php

declare(strict_types=1);

class HTTPResponse implements HTTPResponseInterface
{
    public int     $code;
    public string  $status;
    public string  $message;
    public array   $data;
    public ?string $contentType;
    public ?string $binaryContent;
    public array   $headers;
    public ?string $template;

    public function __construct(int $code, string $status, string $message = "", array $data, ?string $contentType = null, ?string $binaryContent = null, array $headers = [], ?string $template = null)
    {
        $this->code = $code;
        $this->status = $status;
        $this->message = $message;
        $this->data = $data;
        $this->contentType = $contentType;
        $this->binaryContent = $binaryContent;
        $this->headers = $headers;
        $this->template = $template;
    }
}