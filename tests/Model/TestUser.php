<?php

declare(strict_types=1);

class TestUser extends TestAbstractTest
{
    public function testUserConstructor(): void
    {
        $user = new User('admin', new Group('admin'));
        $data = $user->toArray();
        if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
            throw new Exception('test_User problem');
        }
    }
}
