<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Browser;

use DateTimeImmutable;
use Perkamo\SymfonyBundle\Browser\BrowserTokenFactory;
use PHPUnit\Framework\TestCase;

final class BrowserTokenFactoryTest extends TestCase
{
    public function testCreatesSignedBrowserToken(): void
    {
        $factory = new BrowserTokenFactory(
            keyId: 'pk_test_123',
            signingKey: 'browser-signing-secret',
            issuer: 'https://app.example.test',
            audience: 'https://api.perkamo.com/v1/client',
            defaultTtlSeconds: 600,
            streamTtlSeconds: 120,
        );

        $token = $factory->create(
            subject: 'customer_123',
            scopes: ['profile:read', 'events:write', 'events:write'],
            events: ['page.viewed', 'product.viewed'],
            now: new DateTimeImmutable('@1780229100'),
        );

        [$header, $payload] = $this->decode($token->token);

        self::assertSame([
            'alg' => 'HS256',
            'typ' => 'perkamo.client+jwt',
            'kid' => 'pk_test_123',
        ], $header);
        self::assertSame('https://app.example.test', $payload['iss']);
        self::assertSame('https://api.perkamo.com/v1/client', $payload['aud']);
        self::assertSame('customer_123', $payload['sub']);
        self::assertArrayNotHasKey('space', $payload);
        self::assertSame(['profile:read', 'events:write'], $payload['scope']);
        self::assertSame(['page.viewed', 'product.viewed'], $payload['events']);
        self::assertSame(1780229100, $payload['iat']);
        self::assertSame(1780229700, $payload['exp']);
        self::assertMatchesRegularExpression('/^pbt_[a-f0-9]{32}$/', $payload['jti']);
        self::assertSame(1780229700, $token->expiresAt->getTimestamp());
        self::assertTrue($this->hasValidSignature($token->token, 'browser-signing-secret'));
    }

    public function testCreatesShorterStreamTokenWithoutEventAllowlist(): void
    {
        $factory = new BrowserTokenFactory(
            keyId: 'pk_test_123',
            signingKey: 'browser-signing-secret',
            issuer: 'https://app.example.test',
            audience: 'https://api.perkamo.com/v1/client',
            defaultTtlSeconds: 600,
            streamTtlSeconds: 90,
        );

        $token = $factory->createStreamToken(
            subject: 'customer_123',
            scopes: ['stream:read'],
            now: new DateTimeImmutable('@1780229100'),
        );

        [, $payload] = $this->decode($token->token);

        self::assertSame(['stream:read'], $payload['scope']);
        self::assertArrayNotHasKey('events', $payload);
        self::assertSame(1780229190, $payload['exp']);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decode(string $token): array
    {
        [$encodedHeader, $encodedPayload] = explode('.', $token);

        return [
            json_decode($this->base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR),
            json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    private function hasValidSignature(string $token, string $secret): bool
    {
        [$encodedHeader, $encodedPayload, $encodedSignature] = explode('.', $token);
        $expected = rtrim(strtr(base64_encode(hash_hmac(
            'sha256',
            $encodedHeader . '.' . $encodedPayload,
            $secret,
            true,
        )), '+/', '-_'), '=');

        return hash_equals($expected, $encodedSignature);
    }

    private function base64UrlDecode(string $value): string
    {
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
