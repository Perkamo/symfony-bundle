# `perkamo/symfony-bundle`

Symfony bundle for Perkamo backend and browser SDK integrations.

The bundle supports Symfony 6.4 LTS and Symfony 7 on PHP 8.2+. It wires the
server-side [`perkamo/sdk`](https://packagist.org/packages/perkamo/sdk) client
into the Symfony container, exposes browser-token endpoints for
`@perkamo/browser`, and provides Twig helpers for loading the browser SDK from
the approved CDN build.

```bash
composer require perkamo/symfony-bundle
```

## Register The Bundle

Symfony Flex can register the bundle automatically. Without Flex, add it to
`config/bundles.php`:

```php
return [
    Perkamo\SymfonyBundle\PerkamoSymfonyBundle::class => ['all' => true],
];
```

Import the browser SDK routes:

```yaml
# config/routes/perkamo.yaml
perkamo:
  resource: "@PerkamoSymfonyBundle/config/routes.php"
```

## Configure

```yaml
# config/packages/perkamo.yaml
perkamo:
  base_url: "https://api.perkamo.com"
  space: "%env(PERKAMO_SPACE)%"
  api_key: "%env(PERKAMO_SECRET_KEY)%"
  timeout_seconds: 10
  browser:
    bundle:
      version: "0.1.2"
    token_key_id: "%env(PERKAMO_BROWSER_TOKEN_KEY_ID)%"
    token_signing_key: "%env(PERKAMO_BROWSER_TOKEN_SIGNING_KEY)%"
    token_issuer: "%env(PERKAMO_BROWSER_TOKEN_ISSUER)%"
    event_allowlist:
      - page.viewed
      - product.viewed
      - cart.updated
```

The bundle registers `Perkamo\Client`, so backend services can use constructor
injection:

```php
use Perkamo\Client;

final class CheckoutEvents
{
    public function __construct(private readonly Client $perkamo)
    {
    }

    public function completed(string $customerId, string $orderId): void
    {
        $this->perkamo->emit(
            userId: $customerId,
            event: 'purchase.completed',
            context: ['order_id' => $orderId],
            transactionId: $orderId,
        );
    }
}
```

## Browser SDK Endpoints

After importing the routes, the bundle exposes:

- `GET /api/perkamo/browser/config`
- `POST /api/perkamo/token`
- `POST /api/perkamo/stream-token`

The token endpoints use the current Symfony security user identifier by
default. For custom profile IDs, implement
`Perkamo\SymfonyBundle\Security\UserIdResolverInterface` and configure:

```yaml
perkamo:
  browser:
    user_id_resolver: App\Perkamo\CustomerIdResolver
```

The resolver may also set a request attribute named `perkamo_user_id` before the
controller runs.

## Twig Frontend Helper

In a Twig layout:

```twig
{{ perkamo_browser_bundle_script() }}
```

The helper loads the browser bundle from jsDelivr by default and defines
`window.PerkamoSymfony.createClient()`. Frontend code can then create the
browser client without handling token routes manually:

```html
<script>
  window.addEventListener("DOMContentLoaded", function () {
    var perkamo = window.PerkamoSymfony.createClient();
    perkamo.emit("page.viewed", { path: window.location.pathname });
  });
</script>
```

The generated frontend config never includes the server API key or browser
token signing key.

By default, the Twig helper loads the exact configured browser package version:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@perkamo/browser@0.1.2/dist/perkamo-browser.global.min.js"
  defer
></script>
```

Set `perkamo.browser.bundle.version` when upgrading the browser package. To use
a self-hosted bundle globally, configure a custom path:

```yaml
perkamo:
  browser:
    bundle:
      version: "0.1.2"
      path: "/build/perkamo-browser.global.min.js"
```

For a one-off template override, pass your own path expression:

```twig
{{ perkamo_browser_bundle_script(asset("build/perkamo-browser.global.min.js")) }}
```

`perkamo_browser_sdk_script()` remains available as a backward-compatible alias,
but new integrations should use `perkamo_browser_bundle_script()`.

## Security Notes

Use this package only from trusted Symfony backend code. The browser token
signing key is a backend secret and must not be exposed to templates, JSON
config endpoints, browser bundles, mobile apps or embedded widgets.

Browser tokens are HS256 JWTs with `typ=perkamo.client+jwt`, short lifetimes,
the configured browser key id in `kid`, and scoped claims for
`@perkamo/browser`.
