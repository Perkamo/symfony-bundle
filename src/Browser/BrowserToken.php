<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

use DateTimeImmutable;

final class BrowserToken
{
    public function __construct(
        public readonly string $token,
        public readonly DateTimeImmutable $expiresAt,
    ) {
    }
}
