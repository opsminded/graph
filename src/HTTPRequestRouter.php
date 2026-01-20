<?php

declare(strict_types=1);

final class HTTPRequestRouter
{
    private $routes = [
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getUser"],
        ["method" => HTTPRequest::METHOD_POST,   "class_method" => "insertUser"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateUser"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getCategories"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getTypes"],
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

        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getSave"],
        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getSaves"],
        ["method" => HTTPRequest::METHOD_POST,   "class_method" => "insertSave"],
        ["method" => HTTPRequest::METHOD_PUT,    "class_method" => "updateSave"],
        ["method" => HTTPRequest::METHOD_DELETE, "class_method" => "deleteSave"],

        ["method" => HTTPRequest::METHOD_GET,    "class_method" => "getLogs"],
    ];

    public HTTPController $controller;
    
    public function __construct(HTTPControllerInterface $controller)
    {
        $this->controller = $controller;
    }

    public function handle(HTTPRequest $req): HTTPResponse
    {
        $irregularResponse = $this->tryIrregularRoutes($req);
        if ($irregularResponse !== null) {
            return $irregularResponse;
        }

        $regularResponse = $this->tryRegularRoutes($req);
        if ($regularResponse !== null) {
            return $regularResponse;
        }

        return new HTTPInternalServerErrorResponse("method not found in list", ["method" => $req->method, "path" => $req->path]);
    }

    private function tryIrregularRoutes(HTTPRequest $req): ?HTTPResponse
    {
        global $DATA_CYTOSCAPE;
        global $DATA_STYLE_CSS;
        global $DATA_JAVASCRIPT;

        if($req->method == HTTPRequest::METHOD_GET && $req->path == "/")
        {
            $resp = new HTTPOKResponse("welcome to the API", []);
            $resp->template = 'editor';
            return $resp;
        }

        if ($req->method == HTTPRequest::METHOD_GET && $req->path == "/cytoscape.js")
        {
            $resp = new HTTPOKResponse("cytoscape.js", []);
            $resp->contentType = 'Content-Type: text/javascript; charset=UTF-8';
            $resp->binaryContent = $DATA_CYTOSCAPE;
            return $resp;
        }

        if ($req->method == HTTPRequest::METHOD_GET && $req->path == "/style.css")
        {
            $resp = new HTTPOKResponse("style.css", []);
            $resp->contentType = 'Content-Type: text/css; charset=UTF-8';
            $resp->binaryContent = $DATA_STYLE_CSS;
            return $resp;
        }

        if($req->method == HTTPRequest::METHOD_GET && $req->path == "/script.js")
        {
            $resp = new HTTPOKResponse("script.js", []);
            $resp->contentType = 'Content-Type: text/javascript; charset=UTF-8';
            $resp->binaryContent = $DATA_JAVASCRIPT;
            return $resp;
        }

        if ($req->method == HTTPRequest::METHOD_GET && $req->path == "/getImage")
        {
            $type = $req->getParam('img');
            $types = HelperImages::getTypes();
            if (!in_array($type, $types)) {
                return new HTTPNotFoundResponse("image not found", ["requested_type" => $type, "available_types" => $types]);
            }
            
            $resp = new HTTPOKResponse("getImage", []);
            $resp->contentType = 'Content-Type: image/png';
            $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
            $resp->headers[] = "Content-Length: " . strlen($resp->binaryContent);

            $resp->headers[] = "Cache-Control: public, max-age=86400";
            $resp->headers[] = "Expires: " . gmdate("D, d M Y H:i:s", time() + 86400) . " GMT";
            $resp->headers[] = "ETag: \"" . HelperImages::getImageEtag($req->getParam('img')) . "\"";

            $resp->binaryContent = HelperImages::getImageData($req->getParam('img'));
            return $resp;
        }
        return null;
    }

    private function tryRegularRoutes(HTTPRequest $req): ?HTTPResponse
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
                    return new HTTPInternalServerErrorResponse("internal server error", ["exception_message" => $e->getMessage()]);
                }
            }
        }
        return null;
    }
}