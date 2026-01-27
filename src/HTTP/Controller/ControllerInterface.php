<?php

declare(strict_types=1);

interface ControllerInterface
{
    public function getUser(Request $req): ResponseInterface;
    public function insertUser(Request $req): ResponseInterface;
    public function updateUser(Request $req): ResponseInterface;

    public function getCategories(Request $req): ResponseInterface;
    public function getTypes(Request $req): ResponseInterface;

    public function getCytoscapeGraph(Request $req): ResponseInterface;

    public function getNode(Request $req): ResponseInterface;
    public function getNodes(Request $req): ResponseInterface;
    public function insertNode(Request $req): ResponseInterface;
    public function updateNode(Request $req): ResponseInterface;
    public function deleteNode(Request $req): ResponseInterface;

    public function getEdge(Request $req): ResponseInterface;
    public function getEdges(Request $req): ResponseInterface;
    public function insertEdge(Request $req): ResponseInterface;
    public function updateEdge(Request $req): ResponseInterface;
    public function deleteEdge(Request $req): ResponseInterface;

    public function getStatus(Request $req): ResponseInterface;
    public function getNodeStatus(Request $req): ResponseInterface;
    public function updateNodeStatus(Request $req): ResponseInterface;

    public function getProject(Request $req): ResponseInterface;
    public function getProjects(Request $req): ResponseInterface;
    public function insertProject(Request $req): ResponseInterface;
    public function updateProject(Request $req): ResponseInterface;
    public function deleteProject(Request $req): ResponseInterface;
    public function getLogs(Request $req): ResponseInterface;
}