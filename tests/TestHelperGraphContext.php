<?php

declare(strict_types=1);

class TestHelperGraphContext extends TestAbstractTest
{
    public function testGraphContextUpdate(): void
    {
        HelperGraphContext::update('maria', 'admin', '192.168.0.1');
        if (HelperGraphContext::getUser() != 'maria') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperGraphContext::getGroup() != 'admin') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperGraphContext::getClientIP() != '192.168.0.1') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }
    }
}
