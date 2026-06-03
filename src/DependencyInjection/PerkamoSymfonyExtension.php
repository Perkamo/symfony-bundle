<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\DependencyInjection;

use Perkamo\Client;
use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Perkamo\SymfonyBundle\Browser\BrowserTokenFactory;
use Perkamo\SymfonyBundle\Controller\BrowserConfigController;
use Perkamo\SymfonyBundle\Controller\BrowserTokenController;
use Perkamo\SymfonyBundle\Security\SecurityTokenUserIdResolver;
use Perkamo\SymfonyBundle\Security\UserIdResolverInterface;
use Perkamo\SymfonyBundle\Twig\PerkamoTwigExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class PerkamoSymfonyExtension extends Extension
{
    public function getAlias(): string
    {
        return 'perkamo';
    }

    /**
     * @param array<array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{
         *     base_url: string,
         *     space: string|null,
         *     api_key: string,
         *     timeout_seconds: int,
         *     browser: array{
         *         enabled: bool,
         *         bundle: array{
         *             version: string,
         *             path: string|null
         *         },
         *         token_key_id: string,
         *         token_signing_key: string,
         *         token_issuer: string,
         *         token_audience: string|null,
         *         token_ttl_seconds: int,
         *         stream_token_ttl_seconds: int,
         *         scopes: list<string>,
         *         stream_scopes: list<string>,
         *         event_allowlist: list<string>,
         *         user_id_resolver: string
         *     }
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container
            ->register(Client::class, Client::class)
            ->setArguments([
                $config['base_url'],
                $config['api_key'],
                $config['timeout_seconds'],
            ]);
        $container->setAlias('perkamo.client', Client::class);

        if (!$config['browser']['enabled']) {
            return;
        }

        $browser = $config['browser'];
        $space = $this->browserSpace($config['space']);
        $audience = $browser['token_audience'] ?? rtrim($config['base_url'], '/') . '/v1/client';
        $bundle = $browser['bundle'];
        $browserBundlePath = $bundle['path'] ?? sprintf(
            'https://cdn.jsdelivr.net/npm/@perkamo/browser@%s/dist/perkamo-browser.global.min.js',
            $bundle['version'],
        );

        $container
            ->register(SecurityTokenUserIdResolver::class, SecurityTokenUserIdResolver::class)
            ->setArgument(
                '$tokenStorage',
                new Reference(TokenStorageInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            );
        $container->setAlias(UserIdResolverInterface::class, $browser['user_id_resolver']);

        $container
            ->register(BrowserTokenFactory::class, BrowserTokenFactory::class)
            ->setArguments([
                $browser['token_key_id'],
                $browser['token_signing_key'],
                $browser['token_issuer'],
                $audience,
                $space,
                $browser['token_ttl_seconds'],
                $browser['stream_token_ttl_seconds'],
            ]);

        $container
            ->register(BrowserSdkConfigProvider::class, BrowserSdkConfigProvider::class)
            ->setArguments([
                new Reference(UrlGeneratorInterface::class),
                $config['base_url'],
                $bundle['version'],
                $browserBundlePath,
            ]);

        $container
            ->register(BrowserTokenController::class, BrowserTokenController::class)
            ->setArguments([
                new Reference(BrowserTokenFactory::class),
                new Reference(UserIdResolverInterface::class),
                $browser['scopes'],
                $browser['stream_scopes'],
                $browser['event_allowlist'],
            ])
            ->addTag('controller.service_arguments');

        $container
            ->register(BrowserConfigController::class, BrowserConfigController::class)
            ->setArgument('$configProvider', new Reference(BrowserSdkConfigProvider::class))
            ->addTag('controller.service_arguments');

        $container
            ->register(PerkamoTwigExtension::class, PerkamoTwigExtension::class)
            ->setArgument('$configProvider', new Reference(BrowserSdkConfigProvider::class))
            ->addTag('twig.extension');
    }

    private function browserSpace(?string $space): string
    {
        if ($space !== null && trim($space) !== '') {
            return $space;
        }

        throw new InvalidConfigurationException(
            'The "perkamo.space" option is required when "perkamo.browser.enabled" is true.',
        );
    }
}
