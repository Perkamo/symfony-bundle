<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use JsonException;

final class BrowserTokenFactory
{
    public function __construct(
        private readonly string $keyId,
        private readonly string $signingKey,
        private readonly string $issuer,
        private readonly string $audience,
        private readonly int $defaultTtlSeconds = 600,
        private readonly int $streamTtlSeconds = 120,
    ) {
        $this->assertNonEmpty($this->keyId, 'browser key id');
        $this->assertNonEmpty($this->signingKey, 'browser token signing key');
        $this->assertNonEmpty($this->issuer, 'browser token issuer');
        $this->assertNonEmpty($this->audience, 'browser token audience');
        $this->assertTtl($this->defaultTtlSeconds);
        $this->assertTtl($this->streamTtlSeconds);
    }

    /**
     * @param list<string> $scopes
     * @param list<string> $events
     * @throws JsonException
     */
    public function create(
        string $subject,
        array $scopes,
        array $events = [],
        ?DateTimeImmutable $now = null,
        ?int $ttlSeconds = null,
    ): BrowserToken {
        $subject = trim($subject);
        $this->assertNonEmpty($subject, 'browser token subject');

        $ttl = $ttlSeconds ?? $this->defaultTtlSeconds;
        $this->assertTtl($ttl);

        $issuedAt = ($now ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp();
        $expiresAt = (new DateTimeImmutable('@' . $issuedAt))->modify('+' . $ttl . ' seconds');

        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => $subject,
            'scope' => $this->cleanStringList($scopes, 'browser token scope'),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $expiresAt->getTimestamp(),
            'jti' => 'pbt_' . bin2hex(random_bytes(16)),
        ];

        $events = $this->cleanStringList($events, 'browser token event', false);
        if ($events !== []) {
            $payload['events'] = $events;
        }

        return new BrowserToken($this->encodeJwt($payload), $expiresAt);
    }

    /**
     * @param list<string> $scopes
     * @throws JsonException
     */
    public function createStreamToken(
        string $subject,
        array $scopes,
        ?DateTimeImmutable $now = null,
    ): BrowserToken {
        return $this->create($subject, $scopes, [], $now, $this->streamTtlSeconds);
    }

    /**
     * @param array<string, mixed> $payload
     * @throws JsonException
     */
    private function encodeJwt(array $payload): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'perkamo.client+jwt',
            'kid' => $this->keyId,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $this->signingKey, true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function assertNonEmpty(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Perkamo ' . $label . ' must not be empty');
        }
    }

    private function assertTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds < 1 || $ttlSeconds > 1800) {
            throw new InvalidArgumentException('Perkamo browser token TTL must be between 1 and 1800 seconds');
        }
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function cleanStringList(array $values, string $label, bool $requireOne = true): array
    {
        $cleaned = [];
        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                throw new InvalidArgumentException('Perkamo ' . $label . ' must not contain empty values');
            }
            $cleaned[$value] = $value;
        }

        if ($requireOne && $cleaned === []) {
            throw new InvalidArgumentException('Perkamo ' . $label . ' must contain at least one value');
        }

        return array_values($cleaned);
    }
}
