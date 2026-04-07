# Settings

Control the plugin’s output model and behavior defaults.

## Use This Page When

- you need to change CSS delivery or output format
- you want to enable or disable behavior features
- you need to understand what the autosaving settings panels affect

## Steps

### 1. Use The Output Tab

The `Output` tab controls how the plugin generates and serves typography output.

Core controls include:

- `CSS Delivery`
- default `font-display`
- `Minify Generated CSS`
- `Preload Primary Heading and Body Fonts`
- `Remote Connection Hints`

Advanced output controls also cover:

- utility class generation
- class sub-controls by role, alias, category, and family
- variable generation
- variable sub-controls such as role aliases, category aliases, and weight tokens

### 2. Use The Behavior Tab

The `Behavior` tab controls plugin-level features that are not primarily about generated CSS.

Key controls include:

- `Enable Block Editor Font Library Sync`
- `Enable Monospace Role`
- `Hide Onboarding Hints`
- `Delete uploaded fonts on uninstall`

### 3. Understand Autosave

Both settings panels autosave through the plugin REST API. That means:

- you do not need to submit a full page form for normal settings changes
- the UI updates the saved settings state directly
- some settings may still require a page reload to fully reflect the change in the admin UI

### 4. Know What Changes Runtime Output

Output settings can affect:

- whether generated CSS is served from a file or inline
- what `font-display` is emitted for generated `@font-face` rules
- whether minified CSS is written
- whether runtime preloads and remote connection hints are emitted
- what variable and utility-class output is available

Behavior settings can affect:

- whether monospace-specific roles and output exist
- whether imported families are mirrored into the WordPress Block Editor Font Library
- whether onboarding hints remain visible
- whether plugin-managed uploaded files are removed during uninstall

## Notes

- Per-family `font-display` overrides from the library take precedence over the global default for that family.
- If the monospace role is off, related output controls are disabled or hidden.
- Block Editor sync is intentionally cautious on local development environments because loopback TLS trust issues are common there.

## Related Docs

- [Deploy Fonts](deploy-fonts.md)
- [Font Library](font-library.md)
- [Local Development](troubleshooting/local-development.md)
- [Generated CSS](troubleshooting/generated-css.md)
