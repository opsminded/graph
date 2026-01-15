#!/bin/bash

ENDPOINT="http://localhost:8090/index.php";

function assert_content_type_get() {
    address=$1
    expected_content_type=$2
    
    CONTENT_TYPE=$(curl -s -o /dev/null -w "%{content_type}" $address)
    if [ "$CONTENT_TYPE" != "$expected_content_type" ]; then
        echo "address $address"
        echo "Expected content type: $expected_content_type"
        echo "Actual content type: $CONTENT_TYPE"
        exit 1
    fi
}

function assert_content_type_post() {
    address=$1
    data=$2
    expected_content_type=$3
    
    CONTENT_TYPE=$(curl -s -o /dev/null -w "%{content_type}" -X POST -d "$data" $address)
    if [ "$CONTENT_TYPE" != "$expected_content_type" ]; then
        echo "address $address"
        echo "Expected content type: $expected_content_type"
        echo "Actual content type: $CONTENT_TYPE"
        exit 1
    fi
}

function assert_header_get() {
    address=$1
    expected_header=$2
    
    STATUS=$(curl -s -o /tmp/response.json -w "%{http_code}" $address)
    if [ "$STATUS" -ne $expected_header ]; then
        echo "address $address"
        echo "Expected header: $expected_header"
        echo "Actual header: $STATUS"
        echo "Response body:"
        cat /tmp/response.json | jq '.'
        exit 1
    fi
}

function assert_header_post() {
    address=$1
    data=$2
    expected_header=$3

    STATUS=$(curl -s -o /tmp/response.json -w "%{http_code}" -X POST -d "$data" $address)
    if [ "$STATUS" -ne $expected_header ]; then
        echo "address $address"
        echo "Expected header: $expected_header"
        echo "Actual header: $STATUS"
        echo "Response body:"
        cat /tmp/response.json | jq '.'
        exit 1
    fi
}

assert_error_message() {
    address=$1
    expected_message=$2

    ACTUAL_MESSAGE=$(curl -s $address | jq -r .message)
    if [ "$ACTUAL_MESSAGE" != "$expected_message" ]; then
        echo "address $address"
        echo "Expected message: $expected_message"
        echo "Actual message: $ACTUAL_MESSAGE"
        exit 1
    fi
}

assert_data_get() {
    address=$1
    key=$2
    expected_value=$3

    ACTUAL_VALUE=$(curl -s $address | jq -r .data.$key)
    if [ "$ACTUAL_VALUE" != "$expected_value" ]; then
        echo "address $address"
        echo "Expected data.$key: $expected_value"
        echo "Actual data.$key: $ACTUAL_VALUE"
        exit 1
    fi
}

assert_data_post() {
    address=$1
    data=$2
    key=$3
    expected_value=$4

    ACTUAL_VALUE=$(curl -s -X POST -d "$data" $address | jq -r .data.$key)
    if [ "$ACTUAL_VALUE" != "$expected_value" ]; then
        echo "address $address"
        echo "Expected data.$key: $expected_value"
        echo "Actual data.$key: $ACTUAL_VALUE"
        exit 1
    fi
}

##########################################################################################

function test_getUser() {
    echo test_getUser

    address="$ENDPOINT/getUser?id=node1"
    assert_header_get $address 404
    assert_content_type_get $address "application/json; charset=utf-8"
    assert_error_message $address "user not found"
    assert_data_get $address "id" "node1"
}

function test_InsertUser() {
    echo test_InsertUser

    address="$ENDPOINT/insertUser"
    assert_header_post $address '{"id": "node1", "label": "node1", "user_group": "admin"}' 201
    assert_content_type_post $address '{"id": "node1", "label": "node1", "user_group": "admin"}' "application/json; charset=utf-8" 
    assert_data_post $address '{"id": "node1", "label": "node1", "user_group": "admin"}' "id" "node1"
}

function test_updateUser() {
    echo test_updateUser

    address="$ENDPOINT/updateUser"
}

function test_getGraph() {
    echo test_getGraph

    address="$ENDPOINT/getGraph"
    assert_header_get $address 200
    assert_content_type_get $address "application/json; charset=utf-8"
}

function test_getNode() {
    echo test_getNode

    address="$ENDPOINT/getNode?id=node1"
    assert_header_get $address 404
    assert_content_type_get $address "application/json; charset=utf-8"
    assert_error_message $address "node not found"
    assert_data_get $address "id" "node1"
}

function test_getNodes() {
    echo test_getNodes

    address="$ENDPOINT/getNodes"
}

function getNodeParentOf() {
    echo test_getNodeParentOf

    address="$ENDPOINT/getNodeParentOf?node_id=node1"
    assert_header_get $address 404
    assert_content_type_get $address "application/json; charset=utf-8"
    assert_error_message $address "node not found"
    assert_data_get $address "id" "node1"
}

function getDependentNodesOf() {
    echo test_getDependentNodesOf

    address="$ENDPOINT/getDependentNodesOf?node_id=node1"
}

function test_insertNode() {
    echo test_insertNode

    address="$ENDPOINT/insertNode"
}

function test_updateNode() {
    echo test_updateNode

    address="$ENDPOINT/updateNode"
}

function test_deleteNode() {
    echo test_deleteNode

    address="$ENDPOINT/deleteNode"
}

function test_getEdge() {
    echo test_getEdge

    address="$ENDPOINT/getEdge?id=edge1"
    assert_header_get $address 400
    assert_content_type_get $address "application/json; charset=utf-8"
    assert_error_message $address "param 'source' is missing"
    assert_data_get $address "id" "node1"
}

function test_getEdges() {
    echo test_getEdges

    address="$ENDPOINT/getEdges"
}

function test_insertEdge() {
    echo test_insertEdge

    address="$ENDPOINT/insertEdge"
}

function test_updateEdge() {
    echo test_updateEdge

    address="$ENDPOINT/updateEdge"
}

function test_deleteEdge() {
    echo test_deleteEdge

    address="$ENDPOINT/deleteEdge"
}

function test_getStatus() {
    echo test_getStatus

    address="$ENDPOINT/getStatus"
}

function test_getNodeStatus() {
    echo test_getNodeStatus

    address="$ENDPOINT/getNodeStatus?node_id=node1"
}

function test_updateNodeStatus() {
    echo test_updateNodeStatus

    address="$ENDPOINT/updateNodeStatus"
}

function test_getLogs() {
    echo test_getLogs

    address="$ENDPOINT/getLogs"
}

test_getUser
test_InsertUser
test_updateUser
test_getGraph
test_getNode
test_getNodes
test_insertNode
test_updateNode
test_deleteNode
test_getEdge
test_getEdges
test_insertEdge
test_updateEdge
test_deleteEdge
test_getStatus
test_getNodeStatus
test_updateNodeStatus
test_getLogs
echo fim
