<?php
use PHPUnit\Framework\TestCase;
use Opsminded\Graph\Logger;



class LoggerTest extends TestCase
{
    private string $logFilename;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logFilename = sys_get_temp_dir() . '/test_log' . uniqid() . '.log';
        if (file_exists($this->logFilename)) {
            @unlink($this->logFilename);
        }
        $this->logger = new Logger($this->logFilename);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFilename)) {
            @unlink($this->logFilename);
        }
    }

    public function testConstructorCreatesLogFile(): void
    {
        $this->assertFileExists($this->logFilename);
    }

    public function testConstructorThrowsExceptionForInvalidPath(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not open log file:');

        // Suppress the PHP warning that fopen generates for invalid paths
        @new Logger('/invalid/path/that/does/not/exist/test.log');
    }

    public function testDebugWritesMessageToFile(): void
    {
        $this->logger->debug('test message', ['key' => 'value']);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('DEBUG test message', $content);
        $this->assertStringContainsString('{"key":"value"}', $content);
    }

    public function testDebugWithEmptyData(): void
    {
        $this->logger->debug('test message');

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('DEBUG test message', $content);
        $this->assertStringContainsString('[]', $content);
    }

    public function testInfoWritesMessageToFile(): void
    {
        $this->logger->info('test message', ['key' => 'value']);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('INFO test message', $content);
        $this->assertStringContainsString('{"key":"value"}', $content);
    }

    public function testInfoWithEmptyData(): void
    {
        $this->logger->info('test message');

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('INFO test message', $content);
        $this->assertStringContainsString('[]', $content);
    }

    public function testLogWritesMessageToFile(): void
    {
        $this->logger->log('CUSTOM test message', ['key' => 'value']);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('CUSTOM test message', $content);
        $this->assertStringContainsString('{"key":"value"}', $content);
    }

    public function testLogIncludesTimestamp(): void
    {
        $this->logger->log('test', []);

        $content = file_get_contents($this->logFilename);
        // Check for timestamp pattern: YYYY-MM-DD HH:MM:SS
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $content);
    }

    public function testLogWithComplexData(): void
    {
        $complexData = [
            'string' => 'value',
            'number' => 123,
            'float' => 45.67,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'nested' => ['a' => 'b']
        ];

        $this->logger->log('test', $complexData);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('"string":"value"', $content);
        $this->assertStringContainsString('"number":123', $content);
        $this->assertStringContainsString('"bool":true', $content);
    }

    public function testLogWithUnicodeCharacters(): void
    {
        $this->logger->log('test', ['emoji' => '😀', 'chinese' => '你好']);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('😀', $content);
        $this->assertStringContainsString('你好', $content);
    }

    public function testMultipleLogEntries(): void
    {
        $this->logger->debug('first', ['id' => 1]);
        $this->logger->info('second', ['id' => 2]);
        $this->logger->log('CUSTOM third', ['id' => 3]);

        $content = file_get_contents($this->logFilename);
        $lines = explode(PHP_EOL, trim($content));

        $this->assertCount(3, $lines);
        $this->assertStringContainsString('DEBUG first', $lines[0]);
        $this->assertStringContainsString('INFO second', $lines[1]);
        $this->assertStringContainsString('CUSTOM third', $lines[2]);
    }

    public function testLogAppendsToExistingFile(): void
    {
        // Create first logger and write
        $logger1 = new Logger($this->logFilename);
        $logger1->log('first', []);
        unset($logger1);

        // Create second logger and write
        $logger2 = new Logger($this->logFilename);
        $logger2->log('second', []);

        $content = file_get_contents($this->logFilename);
        $this->assertStringContainsString('first', $content);
        $this->assertStringContainsString('second', $content);
    }

    public function testLogEndsWithNewline(): void
    {
        $this->logger->log('test', []);

        $content = file_get_contents($this->logFilename);
        $this->assertStringEndsWith(PHP_EOL, $content);
    }
}