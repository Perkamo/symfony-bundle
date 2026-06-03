<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Controller;

use Perkamo\SymfonyBundle\Browser\BrowserSdkConfigProvider;
use Symfony\Component\HttpFoundation\JsonResponse;

final class BrowserConfigController
{
    public function __construct(
        private readonly BrowserSdkConfigProvider $configProvider,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->configProvider->config());
    }
}
