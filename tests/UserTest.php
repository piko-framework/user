<?php
use PHPUnit\Framework\TestCase;
use piko\User;
use tests\mock\User as UserIdentity;

/**
 * @runTestsInSeparateProcesses
 */
class UserTest extends TestCase
{
    public static function backupSession($data)
    {
        file_put_contents('/tmp/_session_data', serialize($data));
    }

    public static function restoreSession()
    {
        session_start();
        $_SESSION = unserialize(file_get_contents('/tmp/_session_data'));
    }

    protected function getUser()
    {
        return new User([
            'identityClass' => UserIdentity::class,
            'authTimeout' => 5,
            'behaviors' => ['checkAccess' => function($id, $permission) {
                return $id == 1 && $permission == 'test';
            }]
        ]);
    }

    public function testLogin()
    {
        $user = $this->getUser();

        $identity = UserIdentity::findIdentity(1);
        $user->login($identity);
        $this->assertFalse($user->isGuest());
        $this->assertEquals(1, $user->getId());

        self::backupSession($_SESSION);
    }

    /**
     * @depends testLogin
     */
    public function testRetrieveIdentityFromSession()
    {
        self::restoreSession();
        $user = $this->getUser();
        $this->assertEquals('sylvain', $user->getIdentity()->username);
    }

    /**
     * @depends testLogin
     */
    public function testPermissionsAfterLogin()
    {
        self::restoreSession();
        $user = $this->getUser();
        $this->assertFalse($user->isGuest());
        $this->assertFalse($user->can('post'));
        $this->assertTrue($user->can('test'));
        $this->assertTrue($user->can('test')); // Cover permission cache access
    }

    /**
     * @depends testLogin
     */
    public function testPermissionsWithoutAccessChecker()
    {
        self::restoreSession();
        $user = $this->getUser();
        $user->detachBehavior('checkAccess');
        $this->assertFalse($user->can('test'));
    }

    /**
     * @depends testLogin
     */
    public function testLogout()
    {
        self::restoreSession();
        $user = $this->getUser();
        $this->assertFalse($user->isGuest());
        $user->logout();
        $this->assertTrue($user->isGuest());
    }


    public function testEvents()
    {
        $user = new User([
            'on' => ['init' => [function($u) {
                $u->identityClass = UserIdentity::class;
                ini_set('session.name', 'TEST_SESSION');
            }]]
        ]);

        $this->assertEquals('TEST_SESSION', ini_get('session.name'));
        $this->assertEquals(UserIdentity::class, $user->identityClass);

        $user->on('afterLogin', function($identity) {
            $identity->username = 'pascal';
        });

        $user->on('afterLogout', function($identity) {
            $identity->username = 'sylvain';
        });

        $identity = UserIdentity::findIdentity(1);

        $user->login($identity);

        $this->assertEquals('pascal', $identity->username);

        $user->logout();

        $this->assertEquals('sylvain', $identity->username);
    }
}
