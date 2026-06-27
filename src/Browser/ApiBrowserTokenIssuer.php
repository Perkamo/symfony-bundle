<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

use DateTimeImmutable;
use Perkamo\ClientTokenSigner;

/**
 * Self-signs short-lived client tokens with a signing key secret. No round-trip
 * to Perkamo — the secret stays on the backend.
 */
final class ApiBrowserTokenIssuer implements BrowserTokenIssuerInterface
{
    /**
     * @param list<string> $scope
     * @param list<string> $events
     */
    public function __construct(
        private readonly string $kid,
        private readonly string $signingSecret,
        private readonly array $scope = ['customer:read', 'events:write'],
        private readonly array $events = ['*'],
        private readonly int $tokenTtlSeconds = 600,
        private readonly int $streamTokenTtlSeconds = 120,
    ) {
    }

    public function create(string $subject): BrowserToken
    {
        return $this->sign($subject, $this->scope, $this->events, $this->tokenTtlSeconds);
    }

    public function createStreamToken(string $subject): BrowserToken
    {
        return $this->sign($subject, ['stream:read'], null, $this->streamTokenTtlSeconds);
    }

    /**
     * @param list<string>      $scope
     * @param list<string>|null $events
     */
    private function sign(
        string $subject,
        array $scope,
        ?array $events,
        int $ttlSeconds,
    ): BrowserToken {
        $token = ClientTokenSigner::sign(
            $this->kid,
            $this->signingSecret,
            $subject,
            $scope,
            $events,
            $ttlSeconds,
        );
        $expiresAt = (new DateTimeImmutable())->modify(sprintf('+%d seconds', $ttlSeconds));

        return new BrowserToken($token, $expiresAt);
    }
}
