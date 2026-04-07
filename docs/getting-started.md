# Getting Started

Set up Tasty Custom Fonts, add your first families, and understand how the four admin pages fit together.

## Use This Page When

- you just installed the plugin
- you want a first-run path through the current UI
- you need the quickest route from install to live typography

## Steps

### 1. Install And Activate

Use one of these paths:

- install the latest GitHub release ZIP from WordPress `Plugins -> Add New Plugin -> Upload Plugin`
- copy the `etch-fonts/` directory into `wp-content/plugins/` and activate it manually

After activation, open `Tasty Fonts` in the WordPress admin.

### 2. Learn The Four Top-Level Pages

- `Deploy Fonts`: choose draft roles, preview them, save drafts, and apply them sitewide
- `Font Library`: manage families, delivery profiles, fallback stacks, and per-family `font-display`
- `Settings`: control output settings and plugin behavior settings
- `Advanced Tools`: inspect generated CSS, system details, and activity history

### 3. Add Families To The Library

Pick the source that matches your workflow:

- upload local files from the dashboard
- rescan `wp-content/uploads/fonts/`
- import Google Fonts
- import Bunny Fonts
- connect an Adobe Fonts web project

Google self-hosted files are stored under `wp-content/uploads/fonts/google/<family-slug>/`.
Bunny self-hosted files are stored under `wp-content/uploads/fonts/bunny/<family-slug>/`.

### 4. Review Delivery Profiles

In the library, each family can keep one or more delivery profiles. Use the active delivery profile to decide what the runtime should serve.

Typical examples:

- keep a self-hosted profile live
- keep a CDN profile saved for later comparison
- leave a family `In Library Only` until you are ready to use it

### 5. Set Draft Roles

Go to `Deploy Fonts` and choose draft role assignments for:

- `Heading`
- `Body`
- `Monospace`, if the monospace role is enabled in `Settings -> Behavior`

Use `Save Draft` while experimenting.

### 6. Preview Before Publishing

Use the preview workspace to compare draft and live output across:

- editorial content
- card layouts
- reading layouts
- interface text
- code previews

### 7. Apply Sitewide

Use `Apply Sitewide` when the current draft roles are ready to become live. That updates the typography the plugin serves to the frontend, Gutenberg, and Etch.

## Notes

- Draft role changes do not affect live runtime output until you apply them sitewide.
- Admin previews force `font-display: swap` for preview safety, even if the live output uses another `font-display` value.
- GitHub-installed copies can detect future stable releases from the normal WordPress plugins screen through the bundled GitHub updater.

## Related Docs

- [Deploy Fonts](deploy-fonts.md)
- [Font Library](font-library.md)
- [Settings](settings.md)
- [Local Fonts](providers/local-fonts.md)
