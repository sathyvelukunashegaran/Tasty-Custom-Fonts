=== Tasty Custom Fonts ===
Tags: fonts, typography, google fonts, adobe fonts, bunny fonts
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-host local, Google, and Bunny Fonts, with optional Adobe Fonts web project support for Etch, Gutenberg, and the frontend.

== Description ==

Tasty Custom Fonts gives you one place to manage your site's typography stack. Upload local files, import Google Fonts or Bunny Fonts as self-hosted or CDN deliveries, connect an Adobe Fonts web project, and publish the resulting font roles across the frontend, Gutenberg, and Etch.

= Key features =

* Upload WOFF2, WOFF, TTF, and OTF files from the WordPress dashboard.
* Import Google Fonts as self-hosted files or keep them on the Google CDN.
* Import Bunny Fonts as self-hosted files or keep them on the Bunny CDN.
* Connect an Adobe Fonts web project and use Adobe-hosted families alongside local and imported fonts.
* Store multiple delivery profiles per family and switch the active runtime delivery at any time.
* Preview heading, body, and optional monospace roles before applying them sitewide.
* Generate runtime CSS, editor presets, preload hints, and preconnect hints from the same settings flow.
* Optionally sync published families into the core Block Editor Font Library.

= Optional integrations =

Tasty Custom Fonts does not require any companion plugins. Etch, Automatic.css, Bricks, and Oxygen integrations are optional and only activate when those tools are available on the site.

= Multisite =

The plugin is designed for single-site activation and per-site activation inside multisite networks. Network-wide activation is not supported.

= Release channels =

The latest stable release is 1.9.0. Beta and nightly builds are published from GitHub releases for sites that want to test the upcoming line before it becomes stable.

== Installation ==

1. Upload the `etch-fonts` folder to `/wp-content/plugins/`, or install a packaged ZIP from GitHub releases.
2. Activate `Tasty Custom Fonts` from the Plugins screen on the site where you want to manage fonts.
3. Open `Tasty Fonts` in the WordPress admin menu.
4. Add local, Google, Bunny, or Adobe families to the library and assign your live roles when ready.

== Frequently Asked Questions ==

= Do I need Etch or another builder plugin? =

No. The plugin works on standard WordPress sites. Etch, Automatic.css, Bricks, and Oxygen support are optional integrations.

= Does this plugin require a Google Fonts API key? =

Only for live Google Fonts search inside the dashboard. Local uploads, Bunny Fonts, and Adobe Fonts flows do not require a Google API key.

= Can I self-host imported fonts? =

Yes. Google Fonts and Bunny Fonts can both be imported into local storage so the runtime CSS serves your own copies.

= Does it support multisite? =

It supports activating the plugin on individual sites within a multisite network. Network-wide activation is not supported.

= Where are self-hosted font files stored? =

Generated assets and imported files live under `wp-content/uploads/fonts/`, with provider-specific subdirectories for Google and Bunny imports.

== Screenshots ==

1. Deploy Fonts workspace for reviewing draft and live role pairings.
2. Font Library view showing multiple delivery profiles and publish states.
3. Settings screen for output options, integrations, and runtime behavior.
4. Advanced Tools screen for generated CSS, diagnostics, and activity history.

== Changelog ==

= 1.10.0-beta.1 =

* Added a WordPress-style `readme.txt` for distribution metadata and compatibility messaging.
* Documented optional integrations and the multisite activation boundary more explicitly.
* Hardened editor theme JSON font preset injection so invalid or missing schema versions are not forced to a legacy default.

= 1.9.0 =

* Current stable release line.

See `CHANGELOG.md` in the repository for the full project changelog.
