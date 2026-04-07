# Generated CSS

Understand where generated CSS lives, how it is served, and what to check when runtime output looks stale.

## Use This Page When

- the frontend does not reflect recent font changes
- you need to confirm whether the plugin is serving file-based CSS or inline CSS
- you want to verify the generated stylesheet path and current state

## Steps

### 1. Check The Delivery Mode

Open `Settings -> Output` and review `CSS Delivery`.

The plugin can serve runtime output as:

- a generated file
- inline CSS

### 2. Check The Generated File Location

When file delivery is available, the canonical generated stylesheet lives at:

`wp-content/uploads/fonts/.generated/tasty-fonts.css`

### 3. Inspect The Generated CSS Panel

Open `Advanced Tools -> Generated CSS` and compare the current output against what you expect from the library and role settings.

### 4. Check System Details

Use `System Details` to confirm:

- request URL
- file size
- last modified timestamp
- delivery mode and related metadata

## Notes

- The plugin can recognize and migrate a current legacy generated stylesheet from `wp-content/uploads/fonts/tasty-fonts.css` into the canonical `.generated` location.
- Inline delivery is the fallback path when file delivery is disabled or unavailable.
- Output-affecting settings such as `font-display`, minification, variable generation, and utility class generation all change what appears in the generated runtime CSS.

## Related Docs

- [Advanced Tools](../advanced-tools.md)
- [Settings](../settings.md)
- [Imports And Deliveries](imports-and-deliveries.md)
