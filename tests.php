<?php

declare(strict_types=1);

require_once __DIR__ . '/graph.php';

ini_set('xdebug.mode', '1');
xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

function array_diff_recursive($array1, $array2) {
    $diff = [];
    
    foreach ($array1 as $key => $value) {
        if (!array_key_exists($key, $array2)) {
            $diff[$key] = $value;
        } elseif (is_array($value)) {
            if (!is_array($array2[$key])) {
                $diff[$key] = $value;
            } else {
                $recursiveDiff = array_diff_recursive($value, $array2[$key]);
                if (count($recursiveDiff)) {
                    $diff[$key] = $recursiveDiff;
                }
            }
        } elseif ($value !== $array2[$key]) {
            $diff[$key] = $value;
        }
    }
    
    return $diff;
}

function get_test_graph_controller()
{
    GraphContext::$user = new User('test_user', '127.0.0.1', new Group('contributor'));
    $pdo = GraphDatabase::createConnection('sqlite::memory:');
    $databaseLogger = new Logger('database.log');

    $graphDb = new GraphDatabase($pdo, $databaseLogger);

    $serviceLogger = new Logger('service.log');
    $graphService = new GraphService($graphDb, $serviceLogger);

    $controllerLogger = new Logger('controller.log');
    $graphController = new GraphController($graphService, $controllerLogger);
    
    return $graphController;
}

function test_node_good_path()
{
    $graphController = get_test_graph_controller();
    $insertNodeReq = new Request();
    $insertNodeReq->data = ['id' => 'node1', 'label' => 'node1', 'category' => 'business', 'type' => 'server', 'data' => '{}'];
    $resp = $graphController->insertNode($insertNodeReq);
    
    $expected = [
        'id'       => 'node1',
        'label'    => 'node1',
        'category' => 'business',
        'type' => 'server',
        'data' => '{}'
    ];

    if($resp->code != 201) {
        throw new Exception('test_node_good_path');
    }

    if($resp->status != 'success') {
        throw new Exception('test_node_good_path');
    }

    if ($resp->message != 'node inserted') {
        throw new Exception('test_node_good_path');
    }

    $diff = array_diff_recursive($resp->data, $expected);
    if (! empty($diff)) {
        print_r($diff);
        throw new Exception('test_node_good_path');
    }

    $_GET['id'] = 'node1';
    $req = new Request();
    $resp = $graphController->getNode($req);

    if ($resp->code != 200) {
        throw new Exception('test_node_good_path');
    }

    if($resp->status != 'success') {
        throw new Exception('test_node_good_path');
    }

    if ($resp->message != 'node found') {
        throw new Exception('test_node_good_path');
    }

    $expected = [
        'info'     => 'first node',
        'id'       => 'node1',
        'label'    => 'node1',
        'category' => 'business',
        'type'     => 'server',
    ];

    $diff = array_diff_recursive($resp->data['data'], $expected);
    if (! empty($diff)) {
        throw new Exception('test_node_good_path');
    }
    
    $req = new Request();
    $req->data = [
        'id'       => 'node1',
        'label'    => 'node1',
        'category' => 'business',
        'type' => 'server',
        'data' => '{}'
    ];
    $resp = $graphController->updateNode($req);
    print_r($resp);
}

function tests() {
    test_node_good_path();
    echo "fim";
}

tests();

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

// Salvar em arquivo
file_put_contents('coverage.json', json_encode($coverage, JSON_PRETTY_PRINT));

echo 'fim';