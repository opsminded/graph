<?php

declare(strict_types=1);

final class HTTPRequest
{
    public array $data;
    public array $params;
    public string $path;
    public string $method;

    public function __construct()
    {
        $this->params = $_GET;

        $this->method = $_SERVER['REQUEST_METHOD'];

        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        $path = str_replace($scriptName, '', $requestUri);
        $this->path = $path;

        if ($this->method === 'POST' || $this->method === 'PUT') {
            $jsonData = file_get_contents('php://input');
            if ($jsonData) {
                $this->data = json_decode($jsonData, true); 
            } else {
                $this->data = [];
            }
        }
    }

    public function getParam($name): string
    {
        if(isset($this->params[$name])) {
            return strval($this->params[$name]);
        }
        throw new HTTPRequestException("param '{$name}' is missing", [], $this->params, $this->path);
    }
}