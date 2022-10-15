<?php

/**
 * This file is part of Piko - Web micro framework
 *
 * @copyright 2019-2022 Sylvain PHILIP
 * @license LGPL-3.0; see LICENSE.txt
 * @link https://github.com/piko-framework/user
 */

declare(strict_types=1);

namespace piko;

use RuntimeException;

/**
 * Application User base class.
 *
 * @method boolean checkAccess(string|int|null $id, string $permission) A user defined behavior method
 * to check user permission.
 *
 * @author Sylvain PHILIP <contact@sphilip.com>
 */
class User extends Component
{
    /**
     * The class name of the identity object.
     *
     * @var string
     */
    public $identityClass;

    /**
     * The number of seconds in which the user will be logged out automatically if he remains inactive.
     *
     * @var integer
     */
    public $authTimeout;

    /**
     * The identity instance.
     *
     * @var IdentityInterface|null
     */
    protected $identity;

    /**
     * Internal cache of access permissions.
     *
     * @var array<boolean>
     */
    protected $access = [];

    /**
     * {@inheritDoc}
     * @see \piko\Component::init()
     */
    protected function init(): void
    {
        $this->trigger('init', [$this]);

        if (!empty($this->authTimeout && session_status() !== PHP_SESSION_ACTIVE)) {
            ini_set('session.gc_maxlifetime', (string) $this->authTimeout);
        }
    }

    protected function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Get user identity
     *
     * @return IdentityInterface|null The user identity or null if no identity is found.
     */
    public function getIdentity(): ?IdentityInterface
    {
        $this->startSession();

        if ($this->identity === null && isset($_SESSION['_id'])) {
            $class = $this->identityClass;
            $this->identity = $class::findIdentity($_SESSION['_id']);
        }

        return $this->identity;
    }

    /**
     * Get user identifier.
     *
     * @return string|int|null
     */
    public function getId()
    {
        $identity = $this->getIdentity();

        return $identity !== null ? $identity->getId() : null;
    }

    /**
     * Set user identity.
     *
     * @param IdentityInterface $identity The user identity.
     * @return void
     * @throws RuntimeException If identiy doesn't implement IdentityInterface.
     */
    public function setIdentity(IdentityInterface $identity): void
    {
        $this->identity = $identity;
        $this->access = [];
    }

    /**
     * Start the session and set user identity.
     *
     * @param IdentityInterface $identity The user identity.
     * @return void
     */
    public function login(IdentityInterface $identity): void
    {
        $this->startSession();
        $this->trigger('beforeLogin', [$identity]);
        $this->setIdentity($identity);
        $this->trigger('afterLogin', [$identity]);
        $_SESSION['_id'] = $identity->getId();
    }

    /**
     * Destroy the session and remove user identity.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->startSession();
        $this->trigger('beforeLogout', [$this->identity]);
        session_destroy();
        $this->trigger('afterLogout', [$this->identity]);
        $this->identity = null;
        $this->access = [];
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     *
     * @return boolean whether the current user is a guest.
     */
    public function isGuest(): bool
    {
        return $this->getIdentity() === null;
    }

    /**
     * Check if the user can do an action.
     *
     * @param string $permission The permission name.
     * @return boolean
     */
    public function can(string $permission): bool
    {
        if (isset($this->access[$permission])) {
            return $this->access[$permission];
        }

        if (!isset($this->behaviors['checkAccess'])) {
            return false;
        }

        $access = $this->checkAccess($this->getId(), $permission);
        $this->access[$permission] = $access;

        return $access;
    }
}
