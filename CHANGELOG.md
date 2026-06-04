# `perkamo/symfony-bundle` Changelog

## Unreleased

## 0.5.0 - 2026-06-04

### Changed

- Limit supported Symfony versions to 6.4 LTS, 7.4 LTS and 8.x.
- Clarify route import guidance and compatibility documentation.
- Replace local browser JWT signing with Perkamo-issued browser tokens requested
  through `perkamo/sdk`; configure the public browser key with `browser.key`.
- Switch the package license to MIT.

## 0.4.0 - 2026-06-03

### Changed

- Remove `perkamo.space` from bundle configuration and browser token payloads; browser tokens are scoped by their configured browser key id.
- Align the default browser bundle version with `@perkamo/browser` 0.4.0.

## 0.3.1 - 2026-06-03

### Changed

- Pin the default browser bundle version and documentation examples to `@perkamo/browser` 0.3.1.

## 0.3.0 - 2026-06-03

### Changed

- Require `perkamo/sdk` 0.3 and wire backend clients without a redundant Space argument.
- Require `perkamo.space` only when browser token endpoints are enabled, and stop exposing the Space ID in frontend browser SDK config.
- Added Symfony 8 compatibility while keeping Symfony 6.4 LTS and Symfony 7 support.
- Aligned the default browser bundle version with `@perkamo/browser` 0.3.0.

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
