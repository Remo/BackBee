<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Security\Repository;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use BackBee\Security\ApiUserProviderInterface;

/**
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class UserRepository extends EntityRepository implements UserProviderInterface, UserCheckerInterface, ApiUserProviderInterface
{
    public function checkPreAuth(UserInterface $user)
    {
    }

    public function checkPostAuth(UserInterface $user)
    {
    }

    public function loadUserByPublicKey($publicApiKey)
    {
        return $this->findOneBy(array('_api_key_public' => $publicApiKey));
    }

    public function loadUserByUsername($username)
    {
        return $this->findOneBy(array('_login' => $username));
    }

    public function refreshUser(UserInterface $user)
    {
        if (false === $this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Unsupported User class `%s`.', get_class($user)));
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return ($class == 'BackBee\Security\User');
    }

    public function getCollection($params)
    {
        $qb = $this->createQueryBuilder('u');

        $likeParams = ['firstname', 'lastname', 'email', 'login'];

        if (array_key_exists('name', $params)) {
            $nameFilters = explode(' ', $params['name']);

            foreach ($nameFilters as $key => $value) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->like('u._firstname', ':p' . $key),
                    $qb->expr()->like('u._lastname', ':p' . $key)
                ));
                $qb->setParameter(':p' . $key, '%' . $value . '%');
            }

            unset($params['name']);
        }
        foreach ($params as $key => $value) {
            if (property_exists('BackBee\Security\User', '_' . $key)) {
                if (in_array($key, $likeParams)) {
                    $qb->andWhere(
                        $qb->expr()->like('u._' . $key, ':' . $key)
                    );
                    $qb->setParameter(':' . $key, '%' . $value . '%');
                } else {
                    $qb->andWhere(
                        $qb->expr()->eq('u._' . $key, ':' . $key)
                    );
                    $qb->setParameter(':' . $key, $value);
                }
            }
        }


        return $qb->getQuery()->getResult();
    }
}
