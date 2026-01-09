<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';

class TestNode extends AbstractTest
{
    public function testNodeConstructor(): void
    {
        $node = new Node('node1', 'Node 01', 'business', 'server', ['key' => 'value']);

        if ($node->getId() != 'node1' || $node->getLabel() != 'Node 01' || $node->getCategory() != 'business' || $node->getType() != 'server') {
            throw new Exception('test_Node problem - property mismatch');
        }

        $data = $node->getData();
        if ($data['key'] != 'value') {
            throw new Exception('test_Node problem - data mismatch');
        }

        $data = $node->toArray();
        if ($data['id'] != 'node1' || $data['label'] != 'Node 01' || $data['category'] != 'business' || $data['type'] != 'server') {
            throw new Exception('test_Node problem - toArray mismatch');
        }

        if ($data['data']['key'] != 'value') {
            throw new Exception('test_Node problem - toArray data mismatch');
        }

        // Test validation - invalid ID
        try {
            new Node('invalid@id', 'Label', 'business', 'server', []);
            throw new Exception('test_Node problem - should throw exception for invalid ID');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Test validation - label too long
        try {
            new Node('node2', str_repeat('a', 21), 'business', 'server', []);
            throw new Exception('test_Node problem - should throw exception for long label');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Test validation - invalid category
        try {
            new Node('node3', 'Label', 'invalid_category', 'server', []);
            throw new Exception('test_Node problem - should throw exception for invalid category');
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Test validation - invalid type
        try {
            new Node('node4', 'Label', 'business', 'invalid_type', []);
            throw new Exception('test_Node problem - should throw exception for invalid type');
        } catch (InvalidArgumentException $e) {
            // Expected
        }
    }
}
