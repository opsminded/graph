<?php

declare(strict_types=1);

class TestGraphContext extends TestAbstractTest
{
    public function testGraphContextUpdate(): void
    {
        GraphContext::update('maria', 'admin', '192.168.0.1');
        if (GraphContext::getUser() != 'maria') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (GraphContext::getGroup() != 'admin') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (GraphContext::getClientIP() != '192.168.0.1') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }
    }
}
