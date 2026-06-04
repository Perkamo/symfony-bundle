<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Controller;

use DateTimeImmutable;
use Perkamo\SymfonyBundle\Browser\BrowserToken;
use Perkamo\SymfonyBundle\Browser\BrowserTokenIssuerInterface;
use Perkamo\SymfonyBundle\Controller\BrowserTokenController;
use Perkamo\SymfonyBundle\Security\UserIdResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class BrowserTokenControllerTest extends TestCase
{
    public function testReturnsBrowserTokenForResolvedUser(): void
    {
        $issuer = $this->issuer();
        $controller = new BrowserTokenController(
            $issuer,
            $this->resolver('customer_123'),
        );

        $response = $controller->token(new Request());
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('browser-jwt', $body['token']);
        self::assertSame('Bearer', $body['token_type']);
        self::assertSame([
            [
                'type' => 'browser',
                'subject' => 'customer_123',
            ],
        ], $issuer->calls);
    }

    public function testReturnsStreamTokenWithStreamScope(): void
    {
        $issuer = $this->issuer();
        $controller = new BrowserTokenController(
            $issuer,
            $this->resolver('customer_123'),
        );

        $response = $controller->streamToken(new Request());
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('stream-jwt', $body['token']);
        self::assertSame([
            [
                'type' => 'stream',
                'subject' => 'customer_123',
            ],
        ], $issuer->calls);
    }

    public function testRequiresResolvedUser(): void
    {
        $controller = new BrowserTokenController(
            $this->issuer(),
            $this->resolver(null),
        );

        $this->expectException(UnauthorizedHttpException::class);

        $controller->token(new Request());
    }

    private function issuer(): object
    {
        return new class implements BrowserTokenIssuerInterface {
            /**
             * @var list<array<string, mixed>>
             */
            public array $calls = [];

            public function create(string $subject): BrowserToken
            {
                $this->calls[] = [
                    'type' => 'browser',
                    'subject' => $subject,
                ];

                return new BrowserToken(
                    'browser-jwt',
                    new DateTimeImmutable('+10 minutes'),
                );
            }

            public function createStreamToken(string $subject): BrowserToken
            {
                $this->calls[] = [
                    'type' => 'stream',
                    'subject' => $subject,
                ];

                return new BrowserToken(
                    'stream-jwt',
                    new DateTimeImmutable('+2 minutes'),
                );
            }
        };
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

}
