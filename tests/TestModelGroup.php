<?php

declare(strict_types=1);

class TestModelGroup extends TestAbstractTest
{
    public function testGroupConstructor(): void
    {
        $group = new ModelGroup('contributor');
        $data = $group->toArray();
        if($data['id'] != $group->getId()) {
            throw new Exception('test_Group problem');
        }
    }

    public function testGroupException(): void
    {
        try {
            new ModelGroup('xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }
        throw new Exception('test_Group problem');
    }
}
