<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/AbstractTest.php';

class TestUser extends AbstractTest
{
    public function tetUserConstructor(): void
    {
    $user = new User('admin', new Group('admin'));
    $data = $user->toArray();
    if($data['id'] != $user->getId() || $data['group']['id'] != 'admin') {
        throw new Exception('test_UserModel problem');
    }
    }
}
