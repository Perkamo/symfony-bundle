# `perkamo/symfony-bundle` Changelog

## Unreleased

### Breaking

- Align the bundled browser and PHP clients with their canonical response
  models. Application code must read `goals`, `awards`, `perks` and `unlock`
  instead of the deprecated v1 REST aliases.

### Added

- Identify Symfony as the top-level integration on Perkamo API requests so
  version usage can support future upgrade guidance.

## 0.9.0 - 2026-06-26

### Changed

- Require `perkamo/sdk` 0.8 so token routes self-sign client tokens with a
  Perkamo signing key secret.
- Replace `browser.key` configuration with explicit `browser.kid` and
  `browser.secret`, and keep the default browser bundle on 0.8.0.

## 0.9.1 - 2026-07-29

### Fixed

- Allow the current `perkamo/sdk` 0.9 release so Symfony integrations can use
  signed, replay-safe customer erasure without a Composer constraint conflict.

## 0.8.0 - 2026-06-06

### Changed

- Require `perkamo/sdk` 0.7 so Symfony services use the customer-first PHP SDK
  release.
- Align the default browser bundle version with `@perkamo/browser` 0.7.0.

## 0.7.0 - 2026-06-04

### Changed

- Require `perkamo/sdk` 0.6 so Symfony services can use trusted customer
  `identify()` helpers.
- Align the default browser bundle version with `@perkamo/browser` 0.6.0.

## 0.6.0 - 2026-06-04

### Changed

- Require `perkamo/sdk` 0.5 so Symfony services can use program catalog helpers
  and enriched API error metadata.
- Align the default browser bundle version with `@perkamo/browser` 0.5.0.

## 0.5.1 - 2026-06-04

### Changed

- Default Symfony integrations and generated browser clients to the hosted
  Perkamo API, exposing `base_url` only as a custom endpoint override.
- Align the default browser bundle version with `@perkamo/browser` 0.4.1.

## 0.5.0 - 2026-06-04

### Changed

- Limit supported Symfony versions to 6.4 LTS, 7.4 LTS and 8.x.
- Clarify route import guidance and compatibility documentation.
- Replace local browser JWT signing with Perkamo-issued browser tokens requested
  through `perkamo/sdk`; configure the public browser key with `browser.key`.
- Switch the package license to MIT.

## 0.4.0 - 2026-06-03

### Changed

- Remove `perkamo.space` from bundle configuration and browser token payloads; browser tokens are scoped by their configured browser `kid`.
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

## 1.0.0 - 2026-08-06

### Changed

- SDK release

## 1.1.0 - 2026-08-08

### Changed

- SDK release

## 1.2.0 - 2026-08-13

### Changed

- Stable MCP 2026-07-28 support, additive erasure reporting, and corrected SDK runtime identification

## 1.2.1 - 2026-10-13

### Changed

- Refresh dependencies and verify supported SDK integrations
