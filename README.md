# `perkamo/symfony-bundle`

Perkamo integration bundle for Symfony applications.

It provides:

- a configured [`perkamo/sdk`](https://packagist.org/packages/perkamo/sdk)
  backend client service,
- authenticated browser-token and stream-token endpoints for `@perkamo/browser`,
- Twig helpers that load either the pinned CDN browser bundle or a self-hosted
  browser bundle path.

## Compatibility

- PHP 8.2+
- Symfony 6.4 LTS
- Symfony 7.4 LTS
- Symfony 8.x, which requires PHP 8.4+ through Symfony

Symfony 7.0 through 7.3 are intentionally not supported.

## Install

```bash
composer require perkamo/symfony-bundle
```

## Register The Bundle And Routes

Symfony Flex can enable the bundle from the package metadata. Without Flex, add
it to `config/bundles.php`:

```php
return [
    Perkamo\SymfonyBundle\PerkamoSymfonyBundle::class => ['all' => true],
];
```

Symfony does not import third-party bundle routes automatically. Add one route
import in your application:

```yaml
# config/routes/perkamo.yaml
perkamo:
  resource: "@PerkamoSymfonyBundle/config/routes.php"
  type: php
```

The bundle ships route definitions as PHP config so `symfony/yaml` is not a
runtime dependency of this package. Importing the PHP route file from your
application YAML route config is supported by Symfony; `type: php` makes the
loader explicit.

## Configure

```yaml
# config/packages/perkamo.yaml
perkamo:
  api_key: "%env(PERKAMO_SECRET_KEY)%"
  timeout_seconds: 10
  browser:
    key: "%env(PERKAMO_BROWSER_KEY)%"
    bundle:
      version: "0.4.1"
```

Backend event calls use the configured server API key to identify the Space.
The bundle defaults to the hosted Perkamo API. Configure `base_url` only for a
custom, staging or private endpoint.
Browser token routes use `browser.key`, the public browser key from the Perkamo
console. The bundle calls Perkamo with the configured server API key and Perkamo
returns the short-lived browser JWT. No browser token signing secret is stored
in your Symfony app. Browser key access policy is configured in Perkamo and
enforced server-side. The bundle does not send scopes or event allowlists in
token requests. Use `*` on the browser key to allow all current and future
configured events. New browser keys default to the full browser SDK policy:
profile reads, allowed browser events and profile streams.

The bundle registers `Perkamo\Client`, so backend services can use constructor
injection:

```php
use Perkamo\Client;
use Perkamo\EventInput;

final class CheckoutEvents
{
    public function __construct(private readonly Client $perkamo)
    {
    }

    public function completed(string $customerId, string $orderId): void
    {
        $event = EventInput::create($customerId, 'purchase.completed')
            ->withTransactionId($orderId)
            ->withContextValue('order_id', $orderId);

        $this->perkamo->emitEvent($event);
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
`window.PerkamoSymfony.createClient()`. The generated config includes browser
bundle metadata, local token endpoints and a custom API endpoint only when
`base_url` is configured. Frontend code can then create the preview browser
client without handling token routes manually:

```html
<script>
  window.addEventListener("DOMContentLoaded", function () {
    var perkamo = window.PerkamoSymfony.createClient();
    perkamo.emit("page.viewed", { path: window.location.pathname });
  });
</script>
```

The generated frontend config never includes the server API key or browser key.
It also does not expose the Space ID to frontend code.

The generated client uses preview Perkamo `/v1/client/*` routes after it receives
a browser token. Until those routes are enabled for an integration, use the
bundle for backend event emission and token issuing, and return profile state
through your own backend controllers.

By default, the Twig helper loads the exact configured browser package version:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@perkamo/browser@0.4.1/dist/perkamo-browser.global.min.js"
  defer
></script>
```

Set `perkamo.browser.bundle.version` when upgrading the browser package. To use
a self-hosted bundle globally, configure a custom path:

```yaml
perkamo:
  browser:
    bundle:
      version: "0.4.1"
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
routes require an authenticated Symfony user before they ask Perkamo to issue a
short-lived browser JWT. Never expose the server API key to templates, JSON
config endpoints, browser bundles, mobile apps or embedded widgets.

Browser tokens are short-lived credentials issued by Perkamo, not locally signed
by the Symfony application.

## License

MIT
