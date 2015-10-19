<?php

namespace ContinuousPipe\Authenticator\Security;

use ContinuousPipe\Authenticator\Security\Authentication\SystemUserProvider;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiKeyAuthenticator implements SimplePreAuthenticatorInterface
{
    const HEADER_NAME = 'X-Api-Key';

    /**
     * {@inheritdoc}
     */
    public function createToken(Request $request, $providerKey)
    {
        if (null === ($apiKey = $request->headers->get(self::HEADER_NAME))) {
            throw new BadCredentialsException('No API key found');
        }

        return new PreAuthenticatedToken(
            'anon.',
            $apiKey,
            $providerKey
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        if (!$userProvider instanceof SystemUserProvider) {
            throw new \InvalidArgumentException(sprintf(
                'The user provider must be an instance of SystemUserProvider (%s was given).',
                get_class($userProvider)
            ));
        }

        $apiKey = $token->getCredentials();
        $user = $userProvider->getUserForApiKey($apiKey);

        return new PreAuthenticatedToken(
            $user,
            $apiKey,
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}
