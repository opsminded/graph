<?php

declare(strict_types=1);

class TestGroup extends TestAbstractTest
{
    public function testGroupConstructor(): void
    {
        $group = new Group('contributor');
        $data = $group->toArray();
        if($data['id'] != $group->getId()) {
            throw new Exception('test_Group problem');
        }
    }

    public function testGroupException(): void
    {
        try {
            new Group('xpto');
        } catch(InvalidArgumentException $e) {
            return;
        }
        throw new Exception('test_Group problem');
    }
}
