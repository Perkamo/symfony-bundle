<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Twig;

use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Perkamo\SymfonyBundle\Twig\PerkamoTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Markup;

final class PerkamoTwigExtensionTest extends TestCase
{
    public function testExposesBrowserSdkTwigFunctions(): void
    {
        $extension = new PerkamoTwigExtension($this->provider());
        $names = array_map(static fn ($function): string => $function->getName(), $extension->getFunctions());

        self::assertContains('perkamo_browser_sdk_config', $names);
        self::assertContains('perkamo_browser_bundle_config', $names);
        self::assertContains('perkamo_browser_bundle_script', $names);
        self::assertContains('perkamo_browser_sdk_script', $names);
        self::assertInstanceOf(Markup::class, $extension->browserBundleScript());
        self::assertStringContainsString('window.PerkamoSymfony.createClient', (string) $extension->browserBundleScript());
        self::assertStringContainsString(
            'src="/assets/perkamo-browser.global.min.js"',
            (string) $extension->browserBundleScript('/assets/perkamo-browser.global.min.js'),
        );
    }

    private function provider(): BrowserSdkConfigProvider
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name): string => match ($name) {
                'perkamo_browser_token' => '/api/perkamo/token',
                'perkamo_browser_stream_token' => '/api/perkamo/stream-token',
                default => throw new \RuntimeException('Unexpected route ' . $name),
            });

        return new BrowserSdkConfigProvider(
            $router,
            'https://api.perkamo.com',
            '0.4.0',
            'https://cdn.example.test/perkamo-browser.js',
        );
    }
}
