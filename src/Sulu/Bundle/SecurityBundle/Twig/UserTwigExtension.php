<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Twig;

use Doctrine\Common\Cache\Cache;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension to handle users in frontend.
 */
class UserTwigExtension extends AbstractExtension
{
    public function __construct(private Cache $cache, private UserRepository $userRepository)
    {
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_resolve_user', [$this, 'resolveUserFunction']),
        ];
    }

    /**
     * resolves user id to user data.
     *
     * @param int $id id to resolve
     *
     * @return User
     */
    public function resolveUserFunction($id)
    {
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }

        $user = $this->userRepository->findUserById($id);
        if (null === $user) {
            return;
        }

        $this->cache->save($id, $user);

        return $user;
    }
}
