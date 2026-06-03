# `perkamo/symfony-bundle` Changelog

## 0.2.0 - 2026-06-03

### Changed

- Require `perkamo/sdk` 0.2 and align the default browser bundle version with `@perkamo/browser` 0.2.0.
- Keep Symfony configuration, token payloads and browser SDK config on the `space` terminology.

## 0.1.0 - 2026-06-03

### Added

- Added initial Symfony 6.4 and 7 bundle integration for Perkamo.
- Added backend service wiring for `perkamo/sdk`.
- Added browser token, stream token and browser SDK config endpoints.
- Added Twig helpers for loading the browser SDK from the approved CDN build.
