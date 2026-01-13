<?php

declare(strict_types=1);

final class HTTPRequestRouter
{
    private $routes = [
        ['method' => 'GET',    'path' => '/getUser',          'class_method' => 'getUser'],
        ['method' => 'POST',   'path' => '/insertUser',       'class_method' => 'insertUser'],
        ['method' => 'PUT',    'path' => '/updateUser',       'class_method' => 'updateUser'],
        ['method' => 'GET',    'path' => '/getGraph',         'class_method' => 'getGraph'],
        ['method' => 'GET',    'path' => '/getNode',          'class_method' => 'getNode'],
        ['method' => 'GET',    'path' => '/getNodes',         'class_method' => 'getNodes'],
        ['method' => 'POST',   'path' => '/insertNode',       'class_method' => 'insertNode'],
        ['method' => 'PUT',    'path' => '/updateNode',       'class_method' => 'updateNode'],
        ['method' => 'DELETE', 'path' => '/deleteNode',       'class_method' => 'deleteNode'],
        ['method' => 'GET',    'path' => '/getEdge',          'class_method' => 'getEdge'],
        ['method' => 'GET',    'path' => '/getEdges',         'class_method' => 'getEdges'],
        ['method' => 'POST',   'path' => '/insertEdge',       'class_method' => 'insertEdge'],
        ['method' => 'PUT',    'path' => '/updateEdge',       'class_method' => 'updateEdge'],
        ['method' => 'DELETE', 'path' => '/deleteEdge',       'class_method' => 'deleteEdge'],
        ['method' => 'GET',    'path' => '/getStatuses',      'class_method' => 'getStatuses'],
        ['method' => 'GET',    'path' => '/getNodeStatus',    'class_method' => 'getNodeStatus'],
        ['method' => 'PUT',    'path' => '/updateNodeStatus', 'class_method' => 'updateNodeStatus'],
        ['method' => 'GET',    'path' => '/getLogs',          'class_method' => 'getLogs'],
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
            if ($route['method'] == $req->method && $route['path'] == $req->path)
            {
                $method = $route['class_method'];
                $resp = $this->controller->$method($req);
                return $resp;
            }
        }
        return new HTTPInternalServerErrorResponse("method not found in list", ['method' => $req->method, 'path' => $req->path]);
    }
}