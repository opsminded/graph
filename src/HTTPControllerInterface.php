<?php

declare(strict_types=1);

interface HTTPControllerInterface
{
    public function getUser(HTTPRequest $req): HTTPResponseInterface;
    public function insertUser(HTTPRequest $req): HTTPResponseInterface;
    public function updateUser(HTTPRequest $req): HTTPResponseInterface;

    public function getGraph(HTTPRequest $req): HTTPResponseInterface;

    public function getNode(HTTPRequest $req): HTTPResponseInterface;
    public function getNodes(HTTPRequest $req): HTTPResponseInterface;
    public function insertNode(HTTPRequest $req): HTTPResponseInterface;
    public function updateNode(HTTPRequest $req): HTTPResponseInterface;
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface;

    public function getEdge(HTTPRequest $req): HTTPResponseInterface;
    public function getEdges(HTTPRequest $req): HTTPResponseInterface;
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface;
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface;
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface;

    public function getStatuses(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface;
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface;

    public function getLogs(HTTPRequest $req): HTTPResponseInterface;
}