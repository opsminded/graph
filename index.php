<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Opsminded\Graph\Graph;

header('Content-Type: application/json; charset=utf-8');

$dbFile = __DIR__ . '/data/graph.db';
if (!is_dir(dirname($dbFile))) {
    @mkdir(dirname($dbFile), 0755, true);
}

$graph = new Graph($dbFile);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = array_values(array_filter(explode('/', $path)));

function body_json()
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return null;
    }
    return json_decode($raw, true);
}

// Simple router
if (count($parts) === 0) {
    echo json_encode(['ok' => true, 'endpoints' => ['GET /graph','POST /node','PUT /node/{id}','DELETE /node/{id}','POST /edge','DELETE /edge/{source}/{target}','GET /audit']]);
    exit;
}

$resource = $parts[0];

try {
    if ($resource === 'graph' && $method === 'GET') {
        echo json_encode($graph->get());
        exit;
    }

    if ($resource === 'node') {
        if ($method === 'POST') {
            $data = body_json();
            if (!is_array($data) || empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'missing id in body']);
                exit;
            }
            $id = (string)$data['id'];
            unset($data['id']);
            $res = $graph->addNode($id, $data);
            echo json_encode(['success' => (bool)$res]);
            exit;
        }

        if (($method === 'PUT' || $method === 'PATCH') && isset($parts[1])) {
            $id = urldecode($parts[1]);
            $data = body_json() ?? [];
            $res = $graph->updateNode($id, $data);
            echo json_encode(['success' => (bool)$res]);
            exit;
        }

        if ($method === 'DELETE' && isset($parts[1])) {
            $id = urldecode($parts[1]);
            $res = $graph->removeNode($id);
            echo json_encode(['success' => (bool)$res]);
            exit;
        }
    }

    if ($resource === 'edge') {
        if ($method === 'POST') {
            $data = body_json();
            if (!is_array($data) || empty($data['source']) || empty($data['target'])) {
                http_response_code(400);
                echo json_encode(['error' => 'missing source/target']);
                exit;
            }
            $res = $graph->addEdge((string)$data['source'], (string)$data['target']);
            echo json_encode(['success' => (bool)$res]);
            exit;
        }

        if ($method === 'DELETE' && isset($parts[1]) && isset($parts[2])) {
            $source = urldecode($parts[1]);
            $target = urldecode($parts[2]);
            $res = $graph->removeEdge($source, $target);
            echo json_encode(['success' => (bool)$res]);
            exit;
        }
    }

    if ($resource === 'audit' && $method === 'GET') {
        $etype = $_GET['entity_type'] ?? null;
        $eid = $_GET['entity_id'] ?? null;
        echo json_encode($graph->getAuditHistory($etype, $eid));
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'not found']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
