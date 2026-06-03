<?php

declare(strict_types=1);

use Perkamo\SymfonyBundle\Controller\BrowserConfigController;
use Perkamo\SymfonyBundle\Controller\BrowserTokenController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes
        ->add('perkamo_browser_config', '/api/perkamo/browser/config')
        ->controller(BrowserConfigController::class)
        ->methods(['GET']);

    $routes
        ->add('perkamo_browser_token', '/api/perkamo/token')
        ->controller(BrowserTokenController::class . '::token')
        ->methods(['POST']);

    $routes
        ->add('perkamo_browser_stream_token', '/api/perkamo/stream-token')
        ->controller(BrowserTokenController::class . '::streamToken')
        ->methods(['POST']);
};
