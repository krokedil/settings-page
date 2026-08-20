# Changelog

All notable changes of krokedil/settings-page are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added

* Added support for custom sidebar boxes. Boxes are registered through the `boxes` key of the `sidebar` args and render as separate sidebar cards above the plugin resources, each with an `id`, `title` (plain string or locale array), `content` (string or callable) and optional `class`. A box whose rendering throws is skipped and the `krokedil_settings_page_render_error` action is fired, so a broken box cannot blank the settings page. See `docs/sidebar.md`.

### Fixed

* Fixed the sidebar leaving its wrapper `<div>` unclosed, which made the markup after the sidebar rely on browser error recovery for correct nesting.
* Added the missing `sidebar` key to the `register_page()` default args, fixing an undefined array key warning when registering a page without a sidebar.

### Changed

* Moved the sidebar card styling (background, border and padding) from the sidebar container to the sections inside it, so multiple sidebar cards can stack with a gap. A sidebar without custom boxes renders visually unchanged.

------------------
## [1.3.6] - 2026-08-14
### Fixed

* Restored the `Shipping` settings page rendering. The refactor to the `WcSettingsPage` base class dropped the shipping-specific `output_page_content()`, which made shipping pages render through WooCommerce's generic field display instead of the shipping method's own `generate_settings_html()`, so saved values were not populated.

### Changed

* Extended the robust settings output error handling (output buffering, `try/catch`, `fallback_content` and `error_notice` support) to the `WcSettingsPage` base class, so gateway and shipping settings pages also degrade gracefully when a renderer throws. When no `fallback_content` is provided, the form fields are re-rendered using WooCommerce's default field display.

## [1.3.5] - 2026-07-13
### Fixed

* Fixed a PHP 8.1+ deprecation warning (`stripos(): Passing null to parameter #1`) caused by passing `null` to `wp_add_inline_script` when the HelpScout beacon is disabled.

## [1.3.4] - 2026-04-23
### Fixed

* Fixed an issue causing settings page fields to not display correctly.

## [1.3.3] - 2026-04-16
### Changed

* Enhanced error handling to ensure more robust and reliable settings output.

## [1.3.2] - 2026-01-23
### Fixed

* Fixed an issue where the `system-report.json` file was not correctly attached when submitting support requests via the HelpScout beacon.

## [1.3.1] - 2025-12-11
### Changed

* Tweaked the styling of the settings tabs.

### Fixed

* Fixed a fatal error that could occur when the 'Addons' tab was excluded from the settings page.

## [1.3.0] - 2025-06-24
### Added

* Added the option to use a new design and page navigation for the settings page.

## [1.2.1] - 2025-06-10
### Added

* Added support for customizing and translating various text sections.

## [1.2.0] - 2024-10-23
### Added

* Added links to plugin and additional resources.
* Allowed for customizing the partner logo.

### Fixed

* Fixed what version we compare against for WooCommerce to get the system status report.

## [1.1.0] - 2024-09-11
### Changed

* Changed how we generate the content for the support tab.

### Added

* Added localization and swedish translation

### Fixed

* Fixed how we get the system status report from WooCommerce for version 9.1 and above of WooCommerce

## [1.0.0] - 2024-05-23

### Added

* Initial release of the package.
