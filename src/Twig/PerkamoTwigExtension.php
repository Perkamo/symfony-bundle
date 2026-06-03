<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Twig;

use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class PerkamoTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly BrowserSdkConfigProvider $configProvider,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('perkamo_browser_sdk_config', [$this->configProvider, 'config']),
            new TwigFunction('perkamo_browser_bundle_config', [$this->configProvider, 'config']),
            new TwigFunction('perkamo_browser_bundle_script', [$this, 'browserBundleScript'], ['is_safe' => ['html']]),
            new TwigFunction('perkamo_browser_sdk_script', [$this, 'browserBundleScript'], ['is_safe' => ['html']]),
        ];
    }

    public function browserBundleScript(?string $browserBundlePath = null): Markup
    {
        return new Markup($this->configProvider->scriptTag($browserBundlePath), 'UTF-8');
    }
}
