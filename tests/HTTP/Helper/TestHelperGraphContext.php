<?php

declare(strict_types=1);

class TestHelperContext extends TestAbstractTest
{
    public function testGraphContextUpdate(): void
    {
        HelperContext::update('maria', 'admin', '192.168.0.1');
        if (HelperContext::getUser() != 'maria') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperContext::getGroup() != 'admin') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }

        if (HelperContext::getClientIP() != '192.168.0.1') {
            throw new Exception('TODO message. testGraphContextUpdate');
        }
    }
}
