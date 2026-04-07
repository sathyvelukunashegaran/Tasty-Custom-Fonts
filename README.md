# Tasty Custom Fonts

Typography management for Etch, Gutenberg, and the frontend.

Tasty Custom Fonts lets you upload local font files, import Google Fonts or Bunny Fonts as self-hosted or CDN deliveries, connect an Adobe Fonts web project, and manage the live typography stack from one WordPress dashboard. The plugin generates runtime CSS, editor presets, preview tooling, and delivery controls without any build step.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![WordPress 6.1+](https://img.shields.io/badge/WordPress-6.1%2B-21759B?logo=wordpress&logoColor=white)
![License: GPLv2+](https://img.shields.io/badge/License-GPLv2%2B-green)
![Dependencies: none](https://img.shields.io/badge/dependencies-none-brightgreen)

**Works especially well with [EtchWP](https://etch.com) and [Automatic CSS](https://automaticcss.com).**

## What You Can Do

- Upload `WOFF2`, `WOFF`, `TTF`, and `OTF` files from the dashboard.
- Rescan `wp-content/uploads/fonts/` for fonts placed there outside the plugin UI.
- Import Google Fonts as self-hosted files or keep them on the Google CDN.
- Import Bunny Fonts as self-hosted files or keep them on the Bunny CDN.
- Connect an Adobe Fonts web project and use Adobe-hosted families in the same role and preview workflow.
- Store multiple delivery profiles per family and switch which one is active at runtime.
- Work with draft role selections before applying them sitewide.
- Preview editorial, card, reading, interface, and code scenes before publishing.
- Generate CSS variables, optional utility classes, editor presets, preloads, and connection hints from the same settings surface.
- Inspect generated CSS and system details directly from the dashboard.

## Dashboard Layout In 1.6.0

The admin UI is now organized into four top-level pages:

### Deploy Fonts

- Choose draft `Heading`, `Body`, and optional `Monospace` roles.
- Save draft changes without affecting the live site.
- Apply the current draft sitewide when ready.
- Use the built-in preview workspace to compare live output against draft role selections.
- Open snippet panels for generated variables, utility classes, and usage examples.

### Font Library

- Browse every managed family in one place.
- Filter by source, publish state, category, and search text.
- Change a family’s active delivery profile.
- Set per-family fallback stacks and per-family `font-display` overrides.
- Delete variants, delivery profiles, or whole families with the appropriate safeguards.

### Settings

- Adjust output controls such as CSS delivery mode, global `font-display`, minification, preloads, connection hints, variable output, and utility class output.
- Adjust behavior controls such as Block Editor Font Library sync, monospace-role support, onboarding hints, and uninstall cleanup.
- Output and behavior settings autosave through the plugin REST API instead of relying on full page refreshes.

### Advanced Tools

- Inspect the generated runtime stylesheet.
- Download the generated CSS file directly.
- Review system details including storage paths and generated asset metadata.
- Copy diagnostic values directly from the UI.
- Review activity history for imports, scans, delivery changes, settings changes, and asset refreshes.

## Font Sources And Delivery Modes

| Source | Delivery choices | Notes |
| --- | --- | --- |
| Local files | Self-hosted | Upload from the dashboard or rescan `uploads/fonts/`. |
| Google Fonts | Self-hosted or Google CDN | Live search requires a valid Google API key. |
| Bunny Fonts | Self-hosted or Bunny CDN | Search is available in the dashboard. |
| Adobe Fonts | Adobe-hosted | Uses an existing Adobe web project and does not download files locally. |

Each family in the library can store one or more delivery profiles. The active delivery profile controls what the plugin serves at runtime.

## Runtime Behavior

- Self-hosted deliveries generate `@font-face` rules and are included in the generated runtime stylesheet.
- Google CDN, Bunny CDN, and Adobe deliveries are enqueued as external stylesheets when they are active.
- The plugin writes generated CSS to `wp-content/uploads/fonts/.generated/tasty-fonts.css` when file delivery is available.
- If file delivery is disabled or unavailable, the plugin falls back to inline CSS.
- The plugin can emit same-origin WOFF2 preloads for the live heading/body pair.
- The plugin can emit remote preconnect hints for active Google, Bunny, and Adobe deliveries.
- Gutenberg receives matching typography presets.
- Etch receives the same runtime stylesheet URLs through the canvas bridge so preview typography matches the live site.

## Draft And Publishing Model

- Role changes are saved as draft selections first.
- `Apply Sitewide` promotes the current draft roles to live output.
- Families can remain stored in the library without being actively served.
- A family can stay available with multiple delivery profiles even if only one is active.
- When enabled, the monospace role exposes `--font-monospace` for `code` and `pre`.

## Output Controls

Tasty Custom Fonts can generate:

- role variables such as `--font-heading`, `--font-body`, and `--font-monospace`
- optional family variables and category aliases
- optional global weight tokens
- optional utility classes for roles, aliases, categories, and families
- minified or readable CSS output depending on settings

Per-family fallback stacks and per-family `font-display` overrides are managed from the library, while the global output model lives in the Settings page.

## Block Editor And Etch Integration

- The plugin registers runtime families as editor typography presets.
- Managed families can optionally sync into the core Block Editor Font Library.
- Block Editor sync is aware of local-development loopback/TLS issues and provides guidance in the dashboard when sync is likely to fail.
- Admin previews always force `font-display: swap` for preview safety, even when the live runtime output uses another global setting.

## Installation

### Install From GitHub Releases

1. Download the latest ZIP from [GitHub Releases](https://github.com/sathyvelukunashegaran/Tasty-Custom-Fonts/releases).
2. In WordPress, go to `Plugins -> Add New Plugin -> Upload Plugin`.
3. Upload the ZIP and activate `Tasty Custom Fonts`.
4. Open `Tasty Fonts` in the WordPress admin menu.

The packaged plugin directory remains `etch-fonts/` so existing installs can update cleanly.

### Updates For GitHub Installs

The plugin advertises its GitHub repository through `Update URI` and includes a GitHub release updater. If your site was installed from a GitHub release ZIP, future stable releases can be detected from the normal WordPress `Plugins` screen and installed from the attached GitHub release ZIP.

### Manual Install

1. Clone or download this repository.
2. Copy the `etch-fonts` folder into `wp-content/plugins/`.
3. Activate the plugin from the WordPress `Plugins` screen.
4. Open `Tasty Fonts` in the WordPress admin menu.

## Quick Start

### 1. Add families to the library

Use whichever source fits the job:

- `Upload files`
- `Rescan fonts`
- `Google Fonts`
- `Bunny Fonts`
- `Adobe Fonts`

Self-hosted Google imports are stored under `wp-content/uploads/fonts/google/<family-slug>/`.
Self-hosted Bunny imports are stored under `wp-content/uploads/fonts/bunny/<family-slug>/`.

### 2. Review delivery profiles

In the Font Library, choose which delivery profile should be active for each family and decide whether the family should stay runtime-visible.

### 3. Set draft roles

Go to `Deploy Fonts` and choose your draft heading/body roles. Enable the monospace role from `Settings -> Behavior` if you want a third saved role.

### 4. Preview before publishing

Use the preview workspace to compare draft and live output across multiple content scenes.

### 5. Apply sitewide

When the draft looks right, use `Apply Sitewide` to serve the current role CSS across the frontend, Gutenberg, and Etch.

## Requirements

| Component | Requirement |
| --- | --- |
| WordPress | 6.1+ |
| PHP | 8.1+ |
| Etch | Optional |

The plugin works without Etch, but the Etch canvas bridge is most useful when you build with Etch.

## Development

There is no build step, no Composer install, and no npm install.

Useful commands:

```bash
php tests/run.php
node --test tests/js/*.test.cjs
find . -name '*.php' -not -path './output/*' -print0 | xargs -0 -n1 php -l
```

## Translation

Tasty Custom Fonts is translation-ready and uses the `tasty-fonts` text domain.

The translation template is included at `languages/tasty-fonts.pot`.

## Contributing

Pull requests are welcome. For larger changes, open an issue first so the direction is clear before implementation starts.

- Match the WordPress conventions already used in the plugin.
- Run `php tests/run.php` before submitting changes.
- Update the README when user-facing behavior changes.

## License

Tasty Custom Fonts is licensed under the [GNU General Public License v2 or later](LICENSE).
