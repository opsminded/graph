<?php

declare(strict_types=1);

class TestProjectNode extends TestAbstractTest
{
    public function testProjectNodeConstructor(): void
    {
        $node = new ProjectNode('p1', 'n1');
        if ($node->getProjectId() !== 'p1') {
            throw new Exception('Project ID does not match');
        }
        if ($node->getNodeId() !== 'n1') {
            throw new Exception('Name does not match');
        }

        if ($node->toArray() !== ['project_id' => 'p1', 'node_id' => 'n1']) {
            throw new Exception('toArray output does not match expected value');
        }
    }
}
