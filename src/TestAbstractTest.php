<?php

declare(strict_types=1);

abstract class TestAbstractTest
{
    protected function up(): void
    {
    }

    protected function down(): void
    {
    }

    public function run(): void
    {
        $ref = new ReflectionClass($this);
        $methods = $ref->getMethods();
        
        foreach($methods as $method)
        {
            $methodName = $method->getName();
            if(str_starts_with($methodName, 'test'))
            {
                $this->runTest($methodName);
            }
        }
    }

    private function runTest($testName): void
    {
        $class = get_class($this);

        try
        {
            $this->up();
            $this->$testName();
            $this->down();
        } catch(Exception $e) {
            print("{$class} {$testName}\n");
            throw new Exception("Exception found in test '{$testName}'. ({$e->getMessage()})\n");
        }
    }
}
