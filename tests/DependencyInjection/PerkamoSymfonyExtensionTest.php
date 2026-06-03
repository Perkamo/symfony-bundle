<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\DependencyInjection;

use Perkamo\Client;
use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Perkamo\SymfonyBundle\Browser\BrowserTokenFactory;
use Perkamo\SymfonyBundle\Controller\BrowserTokenController;
use Perkamo\SymfonyBundle\DependencyInjection\PerkamoSymfonyExtension;
use Perkamo\SymfonyBundle\Security\SecurityTokenUserIdResolver;
use Perkamo\SymfonyBundle\Security\UserIdResolverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PerkamoSymfonyExtensionTest extends TestCase
{
    public function testRegistersPhpSdkClientAndBrowserServices(): void
    {
        $container = new ContainerBuilder();
        $extension = new PerkamoSymfonyExtension();

        self::assertSame('perkamo', $extension->getAlias());

        $extension->load([$this->config()], $container);

        $clientDefinition = $container->getDefinition(Client::class);
        self::assertSame('https://api.example.test', $clientDefinition->getArgument(0));
        self::assertSame('sk_test_secret', $clientDefinition->getArgument(1));
        self::assertSame(5, $clientDefinition->getArgument(2));
        self::assertSame(Client::class, (string) $container->getAlias('perkamo.client'));

        $factoryDefinition = $container->getDefinition(BrowserTokenFactory::class);
        self::assertSame('pk_test_123', $factoryDefinition->getArgument(0));
        self::assertSame('https://api.example.test/v1/client', $factoryDefinition->getArgument(3));
        self::assertSame(600, $factoryDefinition->getArgument(4));
        self::assertSame(120, $factoryDefinition->getArgument(5));

        self::assertTrue($container->hasDefinition(BrowserSdkConfigProvider::class));
        $configDefinition = $container->getDefinition(BrowserSdkConfigProvider::class);
        self::assertSame('0.4.0', $configDefinition->getArgument(2));
        self::assertSame(
            'https://cdn.jsdelivr.net/npm/@perkamo/browser@0.4.0/dist/perkamo-browser.global.min.js',
            $configDefinition->getArgument(3),
        );
        self::assertSame(
            SecurityTokenUserIdResolver::class,
            (string) $container->getAlias(UserIdResolverInterface::class),
        );

        $controllerDefinition = $container->getDefinition(BrowserTokenController::class);
        self::assertSame(['profile:read', 'events:write'], $controllerDefinition->getArgument(2));
        self::assertSame(['stream:read'], $controllerDefinition->getArgument(3));
        self::assertSame(['page.viewed'], $controllerDefinition->getArgument(4));
    }

    public function testCanDisableBrowserIntegration(): void
    {
        $container = new ContainerBuilder();
        $config = $this->config();
        $config['browser']['enabled'] = false;

        (new PerkamoSymfonyExtension())->load([$config], $container);

        self::assertTrue($container->hasDefinition(Client::class));
        self::assertFalse($container->hasDefinition(BrowserTokenFactory::class));
    }

    public function testBrowserIntegrationDoesNotRequireSpace(): void
    {
        $container = new ContainerBuilder();
        $config = $this->config();

        (new PerkamoSymfonyExtension())->load([$config], $container);

        self::assertTrue($container->hasDefinition(BrowserTokenFactory::class));
    }

    public function testCanUseCustomBrowserBundlePath(): void
    {
        $container = new ContainerBuilder();
        $config = $this->config();
        $config['browser']['bundle']['path'] = '/assets/perkamo-browser.global.min.js';

        (new PerkamoSymfonyExtension())->load([$config], $container);

        $configDefinition = $container->getDefinition(BrowserSdkConfigProvider::class);
        self::assertSame('/assets/perkamo-browser.global.min.js', $configDefinition->getArgument(3));
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'base_url' => 'https://api.example.test',
            'api_key' => 'sk_test_secret',
            'timeout_seconds' => 5,
            'browser' => [
                'bundle' => [
                    'version' => '0.4.0',
                ],
                'token_key_id' => 'pk_test_123',
                'token_signing_key' => 'browser-signing-secret',
                'token_issuer' => 'https://app.example.test',
                'token_ttl_seconds' => 600,
                'stream_token_ttl_seconds' => 120,
                'event_allowlist' => ['page.viewed'],
            ],
        ];
    }
}
