<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(private readonly ApiTokenRepository $tokens) {}

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        // O token é salvo como hash SHA-256: se o banco vazar, os tokens continuam inúteis.
        $token = $this->tokens->findOneBy(['token' => hash('sha256', $accessToken)]);

        if (!$token || $token->isExpired()) {
            throw new BadCredentialsException('Token inválido ou expirado.');
        }

        return new UserBadge($token->getUser()->getUserIdentifier());
    }
}