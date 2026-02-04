<?php

declare(strict_types=1);

final class RequestRouter
{
    private $routes = [
        ["method" => Request::METHOD_GET,    "class_method" => "getUser"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertUser"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateUser"],
        ["method" => Request::METHOD_GET,    "class_method" => "getCategories"],
        ["method" => Request::METHOD_GET,    "class_method" => "getTypes"],

        ["method" => Request::METHOD_GET,    "class_method" => "getCategoryTypes"],
        ["method" => Request::METHOD_GET,    "class_method" => "getTypeNodes"],

        ["method" => Request::METHOD_GET,    "class_method" => "getNode"],
        ["method" => Request::METHOD_GET,    "class_method" => "getNodes"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertNode"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateNode"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteNode"],

        ["method" => Request::METHOD_GET,    "class_method" => "getEdge"],
        ["method" => Request::METHOD_GET,    "class_method" => "getEdges"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertEdge"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateEdge"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteEdge"],

        ["method" => Request::METHOD_PUT,    "class_method" => "updateNodeStatus"],

        ["method" => Request::METHOD_GET,    "class_method" => "getProject"],
        ["method" => Request::METHOD_GET,    "class_method" => "getProjectGraph"],
        ["method" => Request::METHOD_GET,    "class_method" => "getProjectStatus"],
        ["method" => Request::METHOD_GET,    "class_method" => "getProjects"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertProject"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertProjectNode"],

        ["method" => Request::METHOD_DELETE,   "class_method" => "deleteProjectNode"],
    ];

    public Controller $controller;
    
    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function handle(Request $req): Response
    {
        $irregularResponse = $this->tryIrregularRoutes($req);
        if ($irregularResponse !== null) {
            return $irregularResponse;
        }

        $regularResponse = $this->tryRegularRoutes($req);
        if ($regularResponse !== null) {
            return $regularResponse;
        }

        return new InternalServerErrorResponse("method not found in list", ["method" => $req->method, "path" => $req->path]);
    }

    private function tryIrregularRoutes(Request $req): ?Response
    {
        if($req->method == Request::METHOD_GET && $req->path == "/")
        {
            $resp = new OKResponse("welcome to the API", []);
            $resp->template = 'editor.html';
            return $resp;
        }
        return null;
    }

    private function tryRegularRoutes(Request $req): ?Response
    {
        foreach($this->routes as $route)
        {
            if ($route["method"] == $req->method && "/{$route["class_method"]}" == $req->path)
            {
                $method = $route["class_method"];
                try {
                    $resp = $this->controller->$method($req);
                    return $resp;
                } catch (Exception $e) {
                    return new InternalServerErrorResponse("internal server error", ["exception_message" => $e->getMessage()]);
                } catch(Error $err) {
                    return new InternalServerErrorResponse("internal server error", ["error_message" => $err->getMessage()]);
                }
            }
        }
        return null;
    }
}