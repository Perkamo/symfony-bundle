<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Controller;

use Perkamo\SymfonyBundle\Browser\BrowserToken;
use Perkamo\SymfonyBundle\Browser\BrowserTokenIssuerInterface;
use Perkamo\SymfonyBundle\Security\UserIdResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class BrowserTokenController
{
    public function __construct(
        private readonly BrowserTokenIssuerInterface $tokenIssuer,
        private readonly UserIdResolverInterface $userIdResolver,
    ) {
    }

    public function token(Request $request): JsonResponse
    {
        return $this->response(
            $this->tokenIssuer->create(
                $this->requireUserId($request),
            ),
        );
    }

    public function streamToken(Request $request): JsonResponse
    {
        return $this->response(
            $this->tokenIssuer->createStreamToken(
                $this->requireUserId($request),
            ),
        );
    }

    private function requireUserId(Request $request): string
    {
        $userId = $this->userIdResolver->resolveUserId($request);
        if ($userId === null || trim($userId) === '') {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Perkamo browser token endpoints require an authenticated user.',
            );
        }

        return $userId;
    }

    private function response(BrowserToken $token): JsonResponse
    {
        $expiresAt = $token->expiresAt->getTimestamp();

        return new JsonResponse([
            'token' => $token->token,
            'token_type' => 'Bearer',
            'expires_at' => $token->expiresAt->format(DATE_ATOM),
            'expires_in' => max(0, $expiresAt - time()),
        ]);
    }
}
