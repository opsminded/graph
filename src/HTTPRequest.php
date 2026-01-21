<?php

declare(strict_types=1);

final class HTTPRequest
{
    public array $data;
    public array $params;
    public string $path;
    public string $method;
    public string $basePath;

    public const METHOD_GET    = "GET";
    public const METHOD_POST   = "POST";
    public const METHOD_PUT    = "PUT";
    public const METHOD_DELETE = "DELETE";

    public function __construct()
    {
        $this->params = $_GET;
        $this->method = $_SERVER["REQUEST_METHOD"];

        $requestUri = $_SERVER["REQUEST_URI"];
        $requestUri = strtok($requestUri, "?");

        $this->basePath = rtrim(dirname($requestUri), "/\\");
        
        $scriptName = $_SERVER["SCRIPT_NAME"];
        
        $this->path = str_replace($scriptName, "", $requestUri);
        
        if ($this->method === self::METHOD_POST || $this->method === self::METHOD_PUT) {
            $jsonData = file_get_contents("php://input");
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