<?php

declare(strict_types=1);

interface HTTPControllerInterface
{
    public function getUser(HTTPRequest $req): HTTPResponseInterface;
    public function insertUser(HTTPRequest $req): HTTPResponseInterface;
    public function updateUser(HTTPRequest $req): HTTPResponseInterface;

    public function getCytoscapeGraph(HTTPRequest $req): HTTPResponseInterface;

    public function getNode(HTTPRequest $req): HTTPResponseInterface;
    public function getNodes(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeParentOf(HTTPRequest $req): HTTPResponseInterface;
    public function getDependentNodesOf(HTTPRequest $req): HTTPResponseInterface;
    public function insertNode(HTTPRequest $req): HTTPResponseInterface;
    public function updateNode(HTTPRequest $req): HTTPResponseInterface;
    public function deleteNode(HTTPRequest $req): HTTPResponseInterface;

    public function getEdge(HTTPRequest $req): HTTPResponseInterface;
    public function getEdges(HTTPRequest $req): HTTPResponseInterface;
    public function insertEdge(HTTPRequest $req): HTTPResponseInterface;
    public function updateEdge(HTTPRequest $req): HTTPResponseInterface;
    public function deleteEdge(HTTPRequest $req): HTTPResponseInterface;

    public function getStatus(HTTPRequest $req): HTTPResponseInterface;
    public function getNodeStatus(HTTPRequest $req): HTTPResponseInterface;
    public function updateNodeStatus(HTTPRequest $req): HTTPResponseInterface;

    public function getSaves(HTTPRequest $req): HTTPResponseInterface;
    public function insertSave(HTTPRequest $req): HTTPResponseInterface;
    public function updateSave(HTTPRequest $req): HTTPResponseInterface;
    public function deleteSave(HTTPRequest $req): HTTPResponseInterface;

    public function getLogs(HTTPRequest $req): HTTPResponseInterface;

    // public interface for the editors
    public function homePage(HTTPRequest $req): HTTPResponseInterface;
    public function sandboxPage(HTTPRequest $req): HTTPResponseInterface;
}