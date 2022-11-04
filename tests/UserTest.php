<?php
use PHPUnit\Framework\TestCase;
use Piko\User;
use Piko\User\Event\AfterLoginEvent;
use Piko\User\Event\AfterLogoutEvent;
use Piko\User\Event\BeforeLoginEvent;
use Piko\User\Event\BeforeLogoutEvent;
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
            'checkAccess' => function($id, $permission) {
                return $id == 1 && $permission == 'test';
            }
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
        $user->checkAccess = null;
        $this->assertFalse($user->can('test'));
    }

    /**
     * @depends testLogin
     */
    public function testPermissionsWithAccessCheckerReturnsNonBoolean()
    {
        self::restoreSession();
        $user = $this->getUser();
        $user->checkAccess = function($id, $permission) {
            if ($id == 1 && $permission == 'test') {
                return 'OK';
            }
        };
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
        $user = new User();

        $user->on(BeforeLoginEvent::class, function(BeforeLoginEvent $event) {
            $event->identity->id = 2;
        });

        $user->on(AfterLoginEvent::class, function(AfterLoginEvent $event) {
            $event->identity->username = 'pierre';
        });

        $user->on(BeforeLogoutEvent::class, function(BeforeLogoutEvent $event) {
            $event->identity->id = 1;
        });

        $user->on(AfterLogoutEvent::class, function(AfterLogoutEvent $event) {
            $event->identity->username = 'sylvain';
        });

        $identity = UserIdentity::findIdentity(1);
        $user->login($identity);
        $this->assertEquals(2, $identity->id);
        $this->assertEquals('pierre', $identity->username);
        $user->logout();
        $this->assertEquals(1, $identity->id);
        $this->assertEquals('sylvain', $identity->username);
    }
}
