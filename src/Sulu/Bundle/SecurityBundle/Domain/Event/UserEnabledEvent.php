<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin;
use Sulu\Component\Security\Authentication\UserInterface;

class UserEnabledEvent extends DomainEvent
{
    public function __construct(private UserInterface $resourceUser)
    {
        parent::__construct();
    }

    public function getEventType(): string
    {
        return 'enabled';
    }

    public function getResourceKey(): string
    {
        return UserInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->resourceUser->getId();
    }

    public function getResourceSecurityContext(): ?string
    {
        return SecurityAdmin::USER_SECURITY_CONTEXT;
    }

    public function getResourceUser(): UserInterface
    {
        return $this->resourceUser;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceUser->getUserIdentifier();
    }
}
