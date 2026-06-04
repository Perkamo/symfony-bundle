<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\RouteCollection;

final class RoutesTest extends TestCase
{
    public function testRoutesCanBeLoadedBySymfonyAndUseBundlePrefix(): void
    {
        $collection = $this->loadRoutes();

        self::assertCount(3, $collection);

        foreach ($collection->all() as $name => $route) {
            self::assertStringStartsWith('perkamo_', $name);
            self::assertNotSame('', $route->getPath());
        }

        self::assertSame(
            '/api/perkamo/browser/config',
            $collection->get('perkamo_browser_config')?->getPath(),
        );
        self::assertSame(['GET'], $collection->get('perkamo_browser_config')?->getMethods());
        self::assertSame('/api/perkamo/token', $collection->get('perkamo_browser_token')?->getPath());
        self::assertSame(['POST'], $collection->get('perkamo_browser_token')?->getMethods());
        self::assertSame(
            '/api/perkamo/stream-token',
            $collection->get('perkamo_browser_stream_token')?->getPath(),
        );
        self::assertSame(['POST'], $collection->get('perkamo_browser_stream_token')?->getMethods());
    }

    private function loadRoutes(): RouteCollection
    {
        $loader = new PhpFileLoader(new FileLocator(__DIR__ . '/../../config'));
        $routes = $loader->load('routes.php');

        self::assertInstanceOf(RouteCollection::class, $routes);

        return $routes;
    }
}
