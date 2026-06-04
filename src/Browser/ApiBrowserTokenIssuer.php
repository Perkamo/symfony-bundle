<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

use Perkamo\BrowserToken as SdkBrowserToken;
use Perkamo\Client;

final class ApiBrowserTokenIssuer implements BrowserTokenIssuerInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly string $browserKey,
        private readonly int $tokenTtlSeconds = 600,
        private readonly int $streamTokenTtlSeconds = 120,
    ) {
    }

    public function create(string $subject): BrowserToken
    {
        return $this->fromSdkToken(
            $this->client->createBrowserToken(
                $this->browserKey,
                $subject,
                $this->tokenTtlSeconds,
            ),
        );
    }

    public function createStreamToken(string $subject): BrowserToken
    {
        return $this->fromSdkToken(
            $this->client->createBrowserStreamToken(
                $this->browserKey,
                $subject,
                $this->streamTokenTtlSeconds,
            ),
        );
    }

    private function fromSdkToken(SdkBrowserToken $token): BrowserToken
    {
        return new BrowserToken($token->token, $token->expiresAt);
    }
}
