<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Controller;

use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Perkamo\SymfonyBundle\Controller\BrowserConfigController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BrowserConfigControllerTest extends TestCase
{
    public function testReturnsBrowserConfigAsJson(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name): string => match ($name) {
                'perkamo_browser_token' => '/api/perkamo/token',
                'perkamo_browser_stream_token' => '/api/perkamo/stream-token',
                default => throw new \RuntimeException('Unexpected route ' . $name),
            });

        $controller = new BrowserConfigController(new BrowserSdkConfigProvider(
            $router,
            'https://api.perkamo.com',
            '0.7.0',
            'https://cdn.example.test/perkamo-browser.js',
        ));

        $response = $controller();
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertArrayNotHasKey('space', $body);
        self::assertArrayNotHasKey('baseUrl', $body);
        self::assertSame('0.7.0', $body['browserBundleVersion']);
        self::assertSame('https://cdn.example.test/perkamo-browser.js', $body['browserBundlePath']);
        self::assertSame('/api/perkamo/token', $body['tokenEndpoint']);
        self::assertArrayNotHasKey('apiKey', $body);
    }
}
