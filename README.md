# Piko User

[![build](https://github.com/piko-framework/user/actions/workflows/php.yml/badge.svg)](https://github.com/piko-framework/user/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/piko-framework/user/badge.svg?branch=main)](https://coveralls.io/github/piko-framework/user?branch=main)

A lightweight user session manager to login/logout, check permissions and retrieve user identity between sessions.

# Installation

It's recommended that you use Composer to install Piko Router.

```bash
composer require piko/user
```

# Usage

Basic exemple:

```php
use piko\User;
use piko\IdentityInterface;

// Define first your user identity class
class Identity implements IdentityInterface
{
    private static $users = [
        1 => 'paul',
        2 => 'pierre',
    ];

    public $id;
    public $username;

    public static function findIdentity($id)
    {
        if (isset(static::$users[$id])) {
            $user = new static();
            $user->id = $id;
            $user->username = static::$users[$id];

            return $user;
        }

        return null;
    }

    public function getId()
    {
        return $this->id;
    }
}

$user = new User([
    'identityClass' => Identity::class,
    'behaviors' => ['checkAccess' => function($id, $permission) {
        return $id == 1 && $permission == 'test';
    }]
]);

// Login

$user->login(Identity::findIdentity(1));

if (!$user->isGuest()) {
    echo $user->getIdentity()->username; // paul
}

if ($user->can('test')) {
    echo 'I can test';
}

$user->logout();

if ($user->isGuest()) {
    echo $user->getIdentity()->username; // null
    echo 'Not Authenticated';
}

if (!$user->can('test')) {
    echo 'I cannot test';
}

```

Advanced example: [See UserTest.php](tests/UserTest.php)

