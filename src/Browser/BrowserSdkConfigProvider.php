<?php

declare(strict_types=1);

namespace Perkamo\SymfonyBundle\Browser;

use JsonException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class BrowserSdkConfigProvider
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $baseUrl,
        private readonly string $space,
        private readonly string $browserBundleVersion,
        private readonly string $browserBundlePath,
    ) {
    }

    /**
     * @return array{
     *     baseUrl: string,
     *     space: string,
     *     browserBundleVersion: string,
     *     browserBundlePath: string,
     *     tokenEndpoint: string,
     *     streamTokenEndpoint: string
     * }
     */
    public function config(?string $browserBundlePath = null): array
    {
        return [
            'baseUrl' => rtrim($this->baseUrl, '/'),
            'space' => $this->space,
            'browserBundleVersion' => $this->browserBundleVersion,
            'browserBundlePath' => $browserBundlePath ?? $this->browserBundlePath,
            'tokenEndpoint' => $this->urlGenerator->generate('perkamo_browser_token'),
            'streamTokenEndpoint' => $this->urlGenerator->generate('perkamo_browser_stream_token'),
        ];
    }

    /**
     * @throws JsonException
     */
    public function scriptTag(?string $browserBundlePath = null): string
    {
        $browserBundlePath ??= $this->browserBundlePath;
        $config = json_encode(
            $this->config($browserBundlePath),
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
        );
        $scriptSrc = htmlspecialchars($browserBundlePath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<script src="{$scriptSrc}" defer></script>
<script>
(function () {
  var config = {$config};
  window.PerkamoSymfony = window.PerkamoSymfony || {};
  window.PerkamoSymfony.config = config;
  window.PerkamoSymfony.createClient = function (overrides) {
    if (!window.PerkamoBrowser || !window.PerkamoBrowser.createPerkamoBrowserClient) {
      throw new Error("Perkamo browser SDK has not loaded yet.");
    }
    var options = Object.assign({
      baseUrl: config.baseUrl,
      space: config.space,
      getToken: function () {
        return fetch(config.tokenEndpoint, { method: "POST", credentials: "include" })
          .then(function (response) {
            if (!response.ok) throw new Error("Unable to create Perkamo browser token");
            return response.json();
          })
          .then(function (body) { return body.token; });
      },
      getStreamToken: function () {
        return fetch(config.streamTokenEndpoint, { method: "POST", credentials: "include" })
          .then(function (response) {
            if (!response.ok) throw new Error("Unable to create Perkamo stream token");
            return response.json();
          })
          .then(function (body) { return body.token; });
      }
    }, overrides || {});
    return window.PerkamoBrowser.createPerkamoBrowserClient(options);
  };
})();
</script>
HTML;
    }
}
