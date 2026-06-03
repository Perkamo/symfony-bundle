<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Tests\Security;

use Perkamo\SymfonyBundle\Security\SecurityTokenUserIdResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SecurityTokenUserIdResolverTest extends TestCase
{
    public function testUsesRequestAttributeAsExplicitOverride(): void
    {
        $request = new Request();
        $request->attributes->set('perkamo_user_id', ' customer_123 ');

        self::assertSame('customer_123', (new SecurityTokenUserIdResolver())->resolveUserId($request));
    }

    public function testReturnsNullWithoutAuthenticatedUser(): void
    {
        self::assertNull((new SecurityTokenUserIdResolver())->resolveUserId(new Request()));
    }
}
