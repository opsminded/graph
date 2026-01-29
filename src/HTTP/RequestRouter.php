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
        ["method" => Request::METHOD_GET,    "class_method" => "getCytoscapeGraph"],
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
        ["method" => Request::METHOD_GET,    "class_method" => "getStatus"],
        ["method" => Request::METHOD_GET,    "class_method" => "getNodeStatus"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateNodeStatus"],

        ["method" => Request::METHOD_GET,    "class_method" => "getProject"],
        ["method" => Request::METHOD_GET,    "class_method" => "getProjects"],

        ["method" => Request::METHOD_GET,    "class_method" => "getSave"],
        ["method" => Request::METHOD_GET,    "class_method" => "getSaves"],
        ["method" => Request::METHOD_POST,   "class_method" => "insertSave"],
        ["method" => Request::METHOD_PUT,    "class_method" => "updateSave"],
        ["method" => Request::METHOD_DELETE, "class_method" => "deleteSave"],

        ["method" => Request::METHOD_GET,    "class_method" => "getLogs"],
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

        // if ($req->method == Request::METHOD_GET && $req->path == "/getImage")
        // {
        //     $type = $req->getParam('img');
        //     $types = HelperImages::getTypes();
        //     if (!in_array($type, $types)) {
        //         return new NotFoundResponse("image not found", ["requested_type" => $type, "available_types" => $types]);
        //     }
            
        //     $resp = new OKResponse("getImage", []);
        //     $resp->contentType = 'Content-Type: image/png';
        //     $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
        //     $resp->headers[] = "Content-Length: " . strlen($resp->binaryContent);

        //     $resp->headers[] = "Cache-Control: public, max-age=86400";
        //     $resp->headers[] = "Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT";
        //     $resp->headers[] = "ETag: \"" . HelperImages::getImageEtag($req->getParam('img')) . "\"";

        //     $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
        //     return $resp;
        // }
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
                }
            }
        }
        return null;
    }
}