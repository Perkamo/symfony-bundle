<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Browser;

use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BrowserSdkConfigProviderTest extends TestCase
{
    public function testReturnsBrowserSafeConfig(): void
    {
        $provider = new BrowserSdkConfigProvider(
            $this->router(),
            'https://api.perkamo.com/',
            '0.4.1',
            'https://cdn.jsdelivr.net/npm/@perkamo/browser@0.4.1/dist/perkamo-browser.global.min.js',
        );

        self::assertSame([
            'browserBundleVersion' => '0.4.1',
            'browserBundlePath' => 'https://cdn.jsdelivr.net/npm/@perkamo/browser@0.4.1/dist/perkamo-browser.global.min.js',
            'tokenEndpoint' => '/api/perkamo/token',
            'streamTokenEndpoint' => '/api/perkamo/stream-token',
        ], $provider->config());
    }

    public function testCreatesBrowserSdkScriptWithoutSecrets(): void
    {
        $provider = new BrowserSdkConfigProvider(
            $this->router(),
            'https://api.perkamo.com',
            '0.4.1',
            'https://cdn.example.test/perkamo-browser.js',
        );

        $script = $provider->scriptTag();

        self::assertStringContainsString('https://cdn.example.test/perkamo-browser.js', $script);
        self::assertStringContainsString('window.PerkamoSymfony.createClient', $script);
        self::assertStringContainsString('/api/perkamo/token', $script);
        self::assertStringContainsString('"browserBundleVersion":"0.4.1"', $script);
        self::assertStringContainsString('"browserBundlePath":"https://cdn.example.test/perkamo-browser.js"', $script);
        self::assertStringNotContainsString('"baseUrl"', $script);
        self::assertStringNotContainsString('"space"', $script);
        self::assertStringNotContainsString('PERKAMO_SECRET_KEY', $script);
        self::assertStringNotContainsString('token_signing_key', $script);
    }

    public function testCreatesBrowserSdkScriptWithCustomBundlePath(): void
    {
        $provider = new BrowserSdkConfigProvider(
            $this->router(),
            'https://api.perkamo.com',
            '0.4.1',
            'https://cdn.jsdelivr.net/npm/@perkamo/browser@0.4.1/dist/perkamo-browser.global.min.js',
        );

        $script = $provider->scriptTag('/build/perkamo-browser.global.min.js');

        self::assertStringContainsString('src="/build/perkamo-browser.global.min.js"', $script);
        self::assertStringContainsString('"browserBundlePath":"/build/perkamo-browser.global.min.js"', $script);
        self::assertStringNotContainsString('@latest', $script);
    }

    public function testIncludesCustomBaseUrlOnlyWhenConfigured(): void
    {
        $provider = new BrowserSdkConfigProvider(
            $this->router(),
            'https://api.example.test/',
            '0.4.1',
            'https://cdn.example.test/perkamo-browser.js',
        );

        self::assertSame('https://api.example.test', $provider->config()['baseUrl']);
        self::assertStringContainsString('"baseUrl":"https://api.example.test"', $provider->scriptTag());
    }

    private function router(): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name): string => match ($name) {
                'perkamo_browser_token' => '/api/perkamo/token',
                'perkamo_browser_stream_token' => '/api/perkamo/stream-token',
                default => throw new \RuntimeException('Unexpected route ' . $name),
            });

        return $router;
    }
}
