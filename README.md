# `perkamo/symfony-bundle`

Perkamo integration bundle for Symfony applications.

It provides:

- a configured [`perkamo/sdk`](https://packagist.org/packages/perkamo/sdk)
  backend client service,
- authenticated client-token and stream-token endpoints for `@perkamo/browser`,
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

## Register The Bundle

Symfony Flex can enable the bundle from the package metadata. Without Flex, add
it to `config/bundles.php`:

```php
return [
    Perkamo\SymfonyBundle\PerkamoSymfonyBundle::class => ['all' => true],
];
```

## Configure

```yaml
# config/packages/perkamo.yaml
perkamo:
  api_key: "%env(PERKAMO_SECRET_KEY)%"
  timeout_seconds: 10
```

Backend event calls use the configured server API key to identify the Space.
The bundle defaults to the hosted Perkamo API. Configure `base_url` only for a
custom, staging or private endpoint.

Browser token routes are optional. Create a signing key in the Perkamo console
(Settings → Security → Signing keys). It gives you a **KID** (shown in the
creation dialog and then listed in the table) and a **one-time secret** (shown
once, backend-only).
Enable `perkamo.browser` and wire both — the `%env(...)%` names are your own
choice:

```yaml
# config/packages/perkamo.yaml
perkamo:
  api_key: "%env(PERKAMO_SECRET_KEY)%"
  browser:
    enabled: true
    kid: "%env(PERKAMO_SIGNING_KID)%" # KID
    secret: "%env(PERKAMO_SIGNING_SECRET)%" # backend-only
```

| Key      | What it is                                                                    |
| -------- | ----------------------------------------------------------------------------- |
| `kid`    | KID of your signing key; goes in the token header. Safe to expose.            |
| `secret` | The signing key's one-time secret that signs the token. Keep it backend-only. |

When enabled, the bundle verifies the Symfony user session and self-signs a
short-lived client token for `@perkamo/browser`. The token scope and event list
are clamped to the signing key policy by Perkamo during client route
verification; only the secret is sensitive — the `kid` is public.

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

The same autowired `Perkamo\Client` can read trusted admin metadata:

```php
$perkamo->identify($customerId, [
    'email' => $customerEmail,
    'name' => $customerName,
]);

$events = $perkamo->eventCatalog();
```

Use `identify()` for trusted customer traits and `eventCatalog()` for backend
admin screens that need configured event keys and labels.
Non-2xx API responses throw `Perkamo\Exception\PerkamoApiException`, including
request id, retry-after and rate-limit metadata when available.

## Browser SDK Endpoints

After importing the routes, the bundle exposes:

- `GET /api/perkamo/browser/config`
- `POST /api/perkamo/token`
- `POST /api/perkamo/stream-token`

Import the bundle routes only when you enable those browser endpoints:

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

The token endpoints use the current Symfony security user identifier by
default. For custom customer IDs, implement
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

The generated frontend config never includes the server API key, signing secret
or Space ID.

The generated client uses Perkamo `/v1/client/*` routes after it receives a
client token. Until those routes are enabled for an integration, use the
bundle for backend event emission and token issuing, and return customer state
through your own backend controllers.

By default, the Twig helper loads the exact browser package version bundled with
this Symfony bundle:

```html
<script
  src="https://cdn.jsdelivr.net/npm/@perkamo/browser@0.9.0/dist/perkamo-browser.global.min.js"
  defer
></script>
```

Override `perkamo.browser.bundle.version` only when intentionally using a
different compatible browser package. To use a self-hosted bundle globally,
configure a custom path:

```yaml
perkamo:
  browser:
    bundle:
      path: "/build/perkamo-browser.global.min.js"
```

For a one-off template override, pass your own path expression:

```twig
{{ perkamo_browser_bundle_script(asset("build/perkamo-browser.global.min.js")) }}
```

`perkamo_browser_sdk_script()` remains available as a backward-compatible alias,
but new integrations should use `perkamo_browser_bundle_script()`.

## Security Notes

Use this package only from trusted Symfony backend code. The token routes
require an authenticated Symfony user before they self-sign a short-lived client
token. Never expose the server API key or signing secret to templates, JSON
config endpoints, browser bundles, mobile apps or embedded widgets.

Client tokens are short-lived credentials signed locally by the Symfony
application with a Perkamo signing key secret.

## License

MIT
