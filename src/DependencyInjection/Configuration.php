<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\DependencyInjection;

use Perkamo\SymfonyBundle\Security\SecurityTokenUserIdResolver;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('perkamo');

        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('base_url')
                    ->defaultValue('https://api.perkamo.com')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('api_key')
                    ->defaultValue('%env(PERKAMO_SECRET_KEY)%')
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('timeout_seconds')
                    ->min(1)
                    ->defaultValue(10)
                ->end()
                ->arrayNode('browser')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('bundle')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('version')
                                    ->defaultValue('0.6.0')
                                    ->cannotBeEmpty()
                                    ->validate()
                                        ->ifTrue(static fn (mixed $value): bool => !is_string($value) || preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $value) !== 1)
                                        ->thenInvalid('Perkamo browser bundle version must be an exact npm semver version.')
                                    ->end()
                                ->end()
                                ->scalarNode('path')
                                    ->defaultNull()
                                    ->validate()
                                        ->ifTrue(static fn (mixed $value): bool => $value !== null && (!is_string($value) || trim($value) === ''))
                                        ->thenInvalid('Perkamo browser bundle path must be a non-empty string when configured.')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('key')
                            ->defaultValue('%env(PERKAMO_BROWSER_KEY)%')
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('token_ttl_seconds')
                            ->min(60)
                            ->max(1800)
                            ->defaultValue(600)
                        ->end()
                        ->integerNode('stream_token_ttl_seconds')
                            ->min(30)
                            ->max(1800)
                            ->defaultValue(120)
                        ->end()
                        ->scalarNode('user_id_resolver')
                            ->defaultValue(SecurityTokenUserIdResolver::class)
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
