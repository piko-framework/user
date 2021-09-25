<?php
namespace tests\mock;

class AccessChecker
{
    public function checkAccess($userId, $permission)
    {
        return $userId == 1 && $permission == 'test';
    }
}