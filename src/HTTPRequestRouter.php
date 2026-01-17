<?php

declare(strict_types=1);

final class HTTPRequestRouter
{
    private $routes = [
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getUser",          "class_method" => "getUser"],
        ["method" => HTTPRequest::METHOD_POST,   "path" => "/insertUser",       "class_method" => "insertUser"],
        ["method" => HTTPRequest::METHOD_PUT,    "path" => "/updateUser",       "class_method" => "updateUser"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getGraph",         "class_method" => "getGraph"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getNode",          "class_method" => "getNode"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getNodes",         "class_method" => "getNodes"],
        ["method" => HTTPRequest::METHOD_POST,   "path" => "/insertNode",       "class_method" => "insertNode"],
        ["method" => HTTPRequest::METHOD_PUT,    "path" => "/updateNode",       "class_method" => "updateNode"],
        ["method" => HTTPRequest::METHOD_DELETE, "path" => "/deleteNode",       "class_method" => "deleteNode"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getEdge",          "class_method" => "getEdge"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getEdges",         "class_method" => "getEdges"],
        ["method" => HTTPRequest::METHOD_POST,   "path" => "/insertEdge",       "class_method" => "insertEdge"],
        ["method" => HTTPRequest::METHOD_PUT,    "path" => "/updateEdge",       "class_method" => "updateEdge"],
        ["method" => HTTPRequest::METHOD_DELETE, "path" => "/deleteEdge",       "class_method" => "deleteEdge"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getStatus",        "class_method" => "getStatus"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getNodeStatus",    "class_method" => "getNodeStatus"],
        ["method" => HTTPRequest::METHOD_PUT,    "path" => "/updateNodeStatus", "class_method" => "updateNodeStatus"],
        ["method" => HTTPRequest::METHOD_GET,    "path" => "/getLogs",          "class_method" => "getLogs"],
    ];

    public HTTPController $controller;
    
    public function __construct(HTTPControllerInterface $controller)
    {
        $this->controller = $controller;
    }

    public function handle(HTTPRequest $req): HTTPResponse
    {
        foreach($this->routes as $route)
        {
            if ($route["method"] == $req->method && $route["path"] == $req->path)
            {
                $method = $route["class_method"];
                try {
                    $resp = $this->controller->$method($req);
                    return $resp;
                } catch (Exception $e) {
                    return new HTTPInternalServerErrorResponse("internal server error", ["exception_message" => $e->getMessage()]);
                }
            }
        }
        return new HTTPInternalServerErrorResponse("method not found in list", ["method" => $req->method, "path" => $req->path]);
    }
}