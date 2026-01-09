<?php

declare(strict_types=1);

class TestModelUser extends TestAbstractTest
{
    public function testUserConstructor(): void
    {
        $user = new ModelUser('admin', new ModelGroup('admin'));
        $data = $user->toArray();
        if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
            throw new Exception('test_UserModel problem');
        }
    }
}
