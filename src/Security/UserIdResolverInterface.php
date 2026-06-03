<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Security;

use Symfony\Component\HttpFoundation\Request;

interface UserIdResolverInterface
{
    public function resolveUserId(Request $request): ?string;
}
