<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\AuditContext;

class AuditContextTest extends TestCase
{
    protected function tearDown(): void
    {
        AuditContext::clear();
        // Reset any server vars we modified
        $_SERVER = [];
    }

    public function testSetGetClear()
    {
        AuditContext::set('user123', '1.2.3.4');

        $this->assertSame('user123', AuditContext::getUser());
        $this->assertSame('1.2.3.4', AuditContext::getIp());

        $arr = AuditContext::get();
        $this->assertSame('user123', $arr['user_id']);
        $this->assertSame('1.2.3.4', $arr['ip_address']);

        AuditContext::clear();
        $this->assertNull(AuditContext::getUser());
        $this->assertNull(AuditContext::getIp());
    }

    public function testInitFromRequestUsesServerVars()
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '9.9.9.9, 8.8.8.8';
        AuditContext::initFromRequest('alpha');

        $this->assertSame('alpha', AuditContext::getUser());
        $this->assertSame('9.9.9.9', AuditContext::getIp());
    }

    public function testInitFromRequestRealIpAndRemoteAddr()
    {
        AuditContext::clear();

        $_SERVER = [];
        $_SERVER['HTTP_X_REAL_IP'] = '4.4.4.4';
        AuditContext::initFromRequest(null);
        $this->assertSame('4.4.4.4', AuditContext::getIp());

        AuditContext::clear();
        $_SERVER = [];
        $_SERVER['REMOTE_ADDR'] = '5.5.5.5';
        AuditContext::initFromRequest(null);
        $this->assertSame('5.5.5.5', AuditContext::getIp());
    }
}
