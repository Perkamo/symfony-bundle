<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityTokenUserIdResolver implements UserIdResolverInterface
{
    public function __construct(
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function resolveUserId(Request $request): ?string
    {
        $requestUserId = $this->normalizeUserId($request->attributes->get('perkamo_user_id'));
        if ($requestUserId !== null) {
            return $requestUserId;
        }

        $user = $this->tokenStorage?->getToken()?->getUser();
        if ($user instanceof UserInterface) {
            return $this->normalizeUserId($user->getUserIdentifier());
        }

        if (is_string($user) && $user !== 'anon.') {
            return $this->normalizeUserId($user);
        }

        return null;
    }

    private function normalizeUserId(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
