<?php

declare(strict_types=1);

final class RequestRouter
{
    private $routes = [
        ['method' => 'GET',    'path' => '/getGraph',      'class_method' => 'getGraph'],
        ['method' => 'GET',    'path' => '/getNode',       'class_method' => 'getNode'],
        ['method' => 'GET',    'path' => '/getNodes',      'class_method' => 'getNodes'],
        ['method' => 'POST',   'path' => '/insertNode',    'class_method' => 'insertNode'],
        ['method' => 'UPDATE', 'path' => '/updateNode',    'class_method' => 'updateNode'],
        ['method' => 'DELETE', 'path' => '/deleteNode',    'class_method' => 'deleteNode'],
        ['method' => 'GET',    'path' => '/getEdge',       'class_method' => 'getEdge'],
        ['method' => 'GET',    'path' => '/getEdges',      'class_method' => 'getEdges'],
        ['method' => 'POST',   'path' => '/insertEdge',    'class_method' => 'insertEdge'],
        ['method' => 'UPDATE', 'path' => '/updateEdge',    'class_method' => 'updateEdge'],
        ['method' => 'DELETE', 'path' => '/deleteEdge',    'class_method' => 'deleteEdge'],
        ['method' => 'GET',    'path' => '/getStatuses',   'class_method' => 'getStatuses'],
        ['method' => 'GET',    'path' => '/getNodeStatus', 'class_method' => 'getNodeStatus'],
        ['method' => 'GET',    'path' => '/getLogs',       'class_method' => 'getLogs'],
    ];

    public GraphController $controller;
    
    public function __construct(GraphController $controller)
    {
        $this->controller = $controller;
    }

    public function handle(): void
    {
        $method     = $_SERVER['REQUEST_METHOD'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $requestUri = strtok($requestUri, '?');
        
        $path = str_replace($scriptName, '', $requestUri);
        
        $req = new Request();

        foreach($this->routes as $route)
        {
            if ($route['method'] == $method && $route['path'] == $path)
            {
                $method = $route['class_method'];
                $resp = $this->controller->$method($req);
                print_r($resp);
                exit();
            }
        }
    }
}