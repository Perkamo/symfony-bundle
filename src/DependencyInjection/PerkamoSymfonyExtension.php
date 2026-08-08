<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\DependencyInjection;

use Perkamo\Client;
use Perkamo\SdkIdentification;
use Perkamo\SymfonyBundle\Browser\ApiBrowserTokenIssuer;
use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Perkamo\SymfonyBundle\Browser\BrowserTokenIssuerInterface;
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
         *     api_key: string,
         *     timeout_seconds: int,
         *     browser: array{
         *         enabled: bool,
         *         bundle: array{
         *             version: string,
         *             path: string|null
         *         },
         *         kid: string|null,
         *         secret: string|null,
         *         token_ttl_seconds: int,
         *         stream_token_ttl_seconds: int,
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
                SdkIdentification::forPackage('perkamo/symfony-bundle', '1.0.0'),
            ]);
        $container->setAlias('perkamo.client', Client::class);

        if (!$config['browser']['enabled']) {
            return;
        }

        $browser = $config['browser'];
        $kid = $browser['kid'] ?? null;
        $secret = $browser['secret'] ?? null;
        if (!is_string($kid) || trim($kid) === '') {
            throw new InvalidConfigurationException('perkamo.browser.kid is required when perkamo.browser.enabled is true.');
        }
        if (!is_string($secret) || trim($secret) === '') {
            throw new InvalidConfigurationException('perkamo.browser.secret is required when perkamo.browser.enabled is true.');
        }
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
            ->register(ApiBrowserTokenIssuer::class, ApiBrowserTokenIssuer::class)
            ->setArguments([
                trim($kid),
                trim($secret),
                ['customer:read', 'events:write'],
                ['*'],
                $browser['token_ttl_seconds'],
                $browser['stream_token_ttl_seconds'],
            ]);
        $container->setAlias(BrowserTokenIssuerInterface::class, ApiBrowserTokenIssuer::class);

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
                new Reference(BrowserTokenIssuerInterface::class),
                new Reference(UserIdResolverInterface::class),
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
}
