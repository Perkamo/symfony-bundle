<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

interface BrowserTokenIssuerInterface
{
    public function create(string $subject): BrowserToken;

    public function createStreamToken(string $subject): BrowserToken;
}
