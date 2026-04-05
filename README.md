# Tasty Custom Fonts

Typography for Etch, Gutenberg, and the frontend. Import Google Fonts to your own server, upload local font files, scan fonts from `wp-content/uploads/fonts/`, or connect an Adobe Fonts web project while the plugin generates the CSS and runtime hooks you need.

![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![WordPress 6.1+](https://img.shields.io/badge/WordPress-6.1%2B-21759B?logo=wordpress&logoColor=white)
![License: GPLv2+](https://img.shields.io/badge/License-GPLv2%2B-green)
![Dependencies: none](https://img.shields.io/badge/dependencies-none-brightgreen)

**Works especially well with [EtchWP](https://etch.com) and [Automatic CSS](https://automaticcss.com).**

## Features

**Multiple ways to build your font stack**

- Upload `WOFF2`, `WOFF`, `TTF`, or `OTF` files from the `Tasty Fonts` dashboard.
- Import Google Fonts to your own server, with live catalog search when a validated API key is saved.
- Add local files over FTP or SFTP under `wp-content/uploads/fonts/`, then pick them up with `Rescan fonts`.
- Connect an existing Adobe Fonts web project ID to load Adobe-hosted families on the frontend, in Gutenberg, and inside the Etch canvas.

**Generated CSS for quick setup or manual control**

- Builds `@font-face` rules for detected local faces and imported Google faces.
- Saves heading and body roles with fallback stacks you can reuse anywhere.
- Generates snippet outputs for `Site snippet`, `CSS variables`, `Font stacks`, and `Font names`.
- Exposes role aliases like `--font-heading` and `--font-body`, plus variables for the currently selected heading/body families.
- Writes a generated stylesheet to `wp-content/uploads/fonts/tasty-fonts.css` and falls back to inline CSS automatically when file delivery is unavailable.

**WordPress and Etch-aware runtime behavior**

- Registers catalog families as Block Editor typography presets without requiring manual `theme.json` edits.
- Enqueues the generated stylesheet for the frontend, editor, and Etch, while loading font-face rules only in the plugin admin so previews work without restyling the dashboard chrome.
- Includes an Etch canvas bridge so the generated stylesheet can be injected into the builder canvas when needed.

**Library tooling in the admin UI**

- Assign heading/body roles with `Apply Sitewide` or `Save Draft`, and save draft role picks directly from the library cards.
- Preview pairings in `Specimen`, `Card`, `Reading`, and `Interface` modes.
- Search and filter the local library, inspect file details, save per-family fallback stacks, delete individual variants, and remove unused families.
- Keeps an activity log of the latest 100 scans, imports, deletes, and asset refreshes.

## Why self-hosted?

Loading fonts from a third-party CDN creates an extra external request from the visitor's browser. Self-hosting keeps the font files on your own domain, gives you more control over caching, and reduces dependence on remote providers after import time.

Tasty Custom Fonts handles the download step for Google imports once, stores the assets locally, and then serves the resulting styles from your WordPress uploads directory.

Adobe Fonts is the exception: Adobe's web-font terms require Adobe-hosted embed delivery, so this plugin connects to an existing Adobe web project instead of downloading and self-hosting Adobe font files.

## Requirements

| Component | Requirement |
| --- | --- |
| WordPress | 6.1+ |
| PHP | 8.1+ |
| Etch | Optional |

The plugin works without Etch, but its canvas bridge is most useful when you are building with Etch.

## Installation

### Install from GitHub

1. Download the latest release ZIP from [GitHub Releases](https://github.com/sathyvelukunashegaran/Tasty-Custom-Fonts/releases).
2. In WordPress, go to `Plugins -> Add New Plugin -> Upload Plugin`.
3. Upload the ZIP, install it, and activate `Tasty Custom Fonts`.
4. Open `Tasty Fonts` in the WordPress admin menu.

The packaged plugin directory remains `etch-fonts/` for now so existing installs can update without a deactivate/reactivate cycle.

### Manual install

1. Clone or download this repository.
2. Copy the `etch-fonts` folder into `wp-content/plugins/`.
3. Activate the plugin from the WordPress `Plugins` screen.
4. Open `Tasty Fonts` in the WordPress admin menu.

## Getting Started

### Upload font files from the dashboard

1. Open `Tasty Fonts`.
2. Click `Add Font`.
3. Switch to the `Upload files` tab.
4. Add one family group per typeface, then add one row per face you want to upload.
5. Choose a font file for each row. Filenames such as `Abel-400.woff2` or `Inter-700-italic.woff2` let the plugin suggest the family, weight, and style automatically.
6. Review the detected values, adjust the fallback stack if needed, and click `Upload to library`.

### Add local fonts over FTP or SFTP

1. Upload supported files anywhere under `wp-content/uploads/fonts/`.
2. Keep the filenames descriptive so the scanner can detect the family, weight, and style from the basename. Examples:

   ```text
   wp-content/uploads/fonts/Inter-400.woff2
   wp-content/uploads/fonts/Inter-700-italic.woff2
   wp-content/uploads/fonts/editorial/Playfair Display-BoldItalic.ttf
   ```

3. Open `Tasty Fonts` and click `Rescan fonts`.

The local scanner reads the filename, not the folder name, when it determines the family and face details.

### Import from Google Fonts

#### With a Google Fonts API key

1. Open `Tasty Fonts`.
2. Click `Add Font` and stay on the `Google Fonts` tab.
3. In `Google search access`, open `Key Settings`, paste your API key, and click `Save Key`.
4. Use `Search the catalog` to find a family, select the variants you want, and click `Import and self-host`.

#### Without a Google Fonts API key

1. Open `Tasty Fonts -> Add Font -> Google Fonts`.
2. Enter the exact `Family name`.
3. Enter `Manual variants` as a comma-separated list such as `regular,700,700italic`.
4. Click `Import and self-host`.

Google imports are saved into `wp-content/uploads/fonts/google/<family-slug>/` as local `WOFF2` files.

### Connect an Adobe Fonts web project

1. Open `Tasty Fonts`.
2. Click `Add Font` and switch to the `Adobe Fonts` tab.
3. Paste an existing Adobe Fonts `Web Project` ID.
4. Turn on `Load this Adobe-hosted stylesheet` if you want the project active on the site and in editors.
5. Click `Save project`.
6. Use `Resync project` any time you change families or domains in Adobe Fonts.

Adobe families stay hosted by Adobe at `use.typekit.net`; this plugin only validates the stylesheet, caches the detected family names, and makes them available in role selectors, previews, Gutenberg, and the Etch canvas.

### Assign roles and use the generated output

1. In `Tasty Fonts`, choose the `Heading font` and `Body font`.
2. Set fallback stacks for each role.
3. Click `Apply Sitewide` if you want the generated CSS to include role-based selectors for `body` and `h1` through `h6`.
4. Click `Save Draft` if you only want to keep the pairing and copy the generated snippets yourself.
5. Click `Advanced Tools` to access:
   - `Preview` for the built-in pairing demos.
   - `Snippets` for `Site snippet`, `CSS variables`, `Font stacks`, and `Font names`.
   - `System details` for the generated file path, request URL, delivery mode, and library diagnostics.

## How the stylesheet is delivered

The plugin builds a generated stylesheet at `wp-content/uploads/fonts/tasty-fonts.css`. When that file is current and writable, it is enqueued as a regular stylesheet and versioned with a `crc32b` hash of the generated CSS content.

If the file is missing, stale, or cannot be written, the plugin falls back to outputting the same CSS inline so the selected fonts still load. The generated CSS always includes the catalog's `@font-face` rules and can also include the heading/body usage rules when you apply roles with `Apply Sitewide`.

## No external dependencies

Tasty Custom Fonts is a small hand-coded plugin with no Composer packages, npm packages, or bundled frontend libraries.

- No install step beyond activating the plugin.
- No dependency management pipeline to keep in sync.
- No third-party runtime request after a Google family has been imported and stored locally.
- Adobe Fonts support is optional and remains Adobe-hosted by design.

## Translation

Tasty Custom Fonts is translation-ready and uses the `tasty-fonts` text domain.

A POT template is included at `languages/tasty-fonts.pot`. You can use that file with tools like [Poedit](https://poedit.net/) or [Loco Translate](https://localise.biz/wordpress/plugin) to build `.po` and `.mo` translations for your site.

## Screenshots

Screenshots coming soon.

## Contributing

Pull requests are welcome. For larger changes, open an issue first so the direction is clear before implementation starts.

- Follow WordPress coding conventions used by the rest of the plugin.
- Run `php tests/run.php` before submitting changes.
- Update this README when user-facing behavior or requirements change.

## License

Tasty Custom Fonts is licensed under the [GNU General Public License v2 or later](LICENSE).

See [LICENSE](LICENSE) for the full text.
