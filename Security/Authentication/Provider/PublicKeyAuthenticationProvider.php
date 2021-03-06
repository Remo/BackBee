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

namespace BackBee\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use BackBee\Security\Encoder\RequestSignatureEncoder;
use BackBee\Security\Exception\SecurityException;
use BackBee\Security\Token\PublicKeyToken;

/**
 * Authentication provider for username/password firewall.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class PublicKeyAuthenticationProvider extends BBAuthenticationProvider
{
    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (false === $this->supports($token)) {
            return;
        }

        $publicKey = $token->getUsername();

        if (null === $nonce = $this->readNonceValue($token->getNonce())) {
            $this->onInvalidAuthentication();
        }

        $user = $this->user_provider->loadUserByPublicKey($publicKey);

        if (null === $user) {
            $this->onInvalidAuthentication();
        }

        $token->setUser($user);

        $signature_encoder = new RequestSignatureEncoder();
        if (false === $signature_encoder->isApiSignatureValid($token, $nonce[1])) {
            $this->onInvalidAuthentication();
        }

        if (time() > $nonce[0] + $this->lifetime) {
            throw new SecurityException('Prior authentication expired', SecurityException::EXPIRED_AUTH);
        }

        $authenticated_token = new PublicKeyToken($user->getRoles());
        $authenticated_token->setUser($user);

        return $authenticated_token;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof PublicKeyToken;
    }

    /**
     * Throw a SecurityException with Invalid authentication informations and 401 as status code.
     */
    private function onInvalidAuthentication()
    {
        throw new SecurityException('Invalid authentication informations', SecurityException::INVALID_CREDENTIALS);
    }
}
