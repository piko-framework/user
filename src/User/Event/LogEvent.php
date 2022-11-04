<?php

/**
 * This file is part of Piko Framework
 *
 * @copyright 2019-2022 Sylvain Philip
 * @license LGPL-3.0-or-later; see LICENSE.txt
 * @link https://github.com/piko-framework/user
 */

declare(strict_types=1);

namespace Piko\User\Event;

use Piko\Event;
use Piko\User\IdentityInterface;

/**
 * Event emitted during user login
 *
 * @author Sylvain Philip <contact@sphilip.com>
 */
abstract class LogEvent extends Event
{
    /**
     * @var IdentityInterface
     */
    public $identity;

    /**
     * @param IdentityInterface $identity
     */
    public function __construct(IdentityInterface $identity)
    {
        $this->identity = $identity;
    }
}
