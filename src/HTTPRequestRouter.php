<?php

declare(strict_types=1);

final class HTTPRequestRouter
{
    private $routes = [
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getUser"],
        ["method" => HTTPRequest::METHOD_POST,   "class_method" => "insertUser"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateUser"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getCytoscapeGraph"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getNode"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getNodes"],
        ["method" => HTTPRequest::METHOD_POST,   "class_method" => "insertNode"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateNode"],
        ["method" => HTTPRequest::METHOD_DELETE, "class_method" => "deleteNode"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getEdge"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getEdges"],
        ["method" => HTTPRequest::METHOD_POST,   "class_method" => "insertEdge"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateEdge"],
        ["method" => HTTPRequest::METHOD_DELETE, "class_method" => "deleteEdge"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getStatus"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getNodeStatus"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateNodeStatus"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getLogs"],
    ];

    public HTTPController $controller;
    
    public function __construct(HTTPControllerInterface $controller)
    {
        $this->controller = $controller;
    }

    public function handle(HTTPRequest $req): HTTPResponse
    {
        if($req->method == HTTPRequest::METHOD_GET && $req->path == "/")
        {
            $resp = new HTTPOKResponse("welcome to the API", []);
            $resp->template = 'index.html';
            return $resp;
        }

        foreach($this->routes as $route)
        {
            if ($route["method"] == $req->method && "/{$route["class_method"]}" == $req->path)
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