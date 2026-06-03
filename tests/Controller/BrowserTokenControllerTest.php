<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Controller;

use Perkamo\SymfonyBundle\Browser\BrowserTokenFactory;
use Perkamo\SymfonyBundle\Controller\BrowserTokenController;
use Perkamo\SymfonyBundle\Security\UserIdResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class BrowserTokenControllerTest extends TestCase
{
    public function testReturnsBrowserTokenForResolvedUser(): void
    {
        $controller = new BrowserTokenController(
            $this->factory(),
            $this->resolver('customer_123'),
            ['profile:read', 'events:write'],
            ['stream:read'],
            ['page.viewed'],
        );

        $response = $controller->token(new Request());
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        [, $payload] = $this->decode($body['token']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Bearer', $body['token_type']);
        self::assertSame('customer_123', $payload['sub']);
        self::assertSame(['profile:read', 'events:write'], $payload['scope']);
        self::assertSame(['page.viewed'], $payload['events']);
    }

    public function testReturnsStreamTokenWithStreamScope(): void
    {
        $controller = new BrowserTokenController(
            $this->factory(),
            $this->resolver('customer_123'),
            ['profile:read', 'events:write'],
            ['stream:read'],
            [],
        );

        $response = $controller->streamToken(new Request());
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        [, $payload] = $this->decode($body['token']);

        self::assertSame(['stream:read'], $payload['scope']);
        self::assertArrayNotHasKey('events', $payload);
    }

    public function testRequiresResolvedUser(): void
    {
        $controller = new BrowserTokenController(
            $this->factory(),
            $this->resolver(null),
            ['profile:read'],
            ['stream:read'],
            [],
        );

        $this->expectException(UnauthorizedHttpException::class);

        $controller->token(new Request());
    }

    private function factory(): BrowserTokenFactory
    {
        return new BrowserTokenFactory(
            keyId: 'pk_test_123',
            signingKey: 'browser-signing-secret',
            issuer: 'https://app.example.test',
            audience: 'https://api.perkamo.com/v1/client',
            space: 'commerce-test',
        );
    }

    private function resolver(?string $userId): UserIdResolverInterface
    {
        return new class ($userId) implements UserIdResolverInterface {
            public function __construct(private readonly ?string $userId)
            {
            }

            public function resolveUserId(Request $request): ?string
            {
                return $this->userId;
            }
        };
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

    private function base64UrlDecode(string $value): string
    {
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
