# Adobe Fonts

Use an Adobe Fonts web project as a hosted family source inside the same role and preview workflow.

## Use This Page When

- you already manage fonts through Adobe Fonts
- you want Adobe families available in selectors and previews without self-hosting them
- you need to understand the Adobe-specific delivery model

## Steps

### 1. Add A Web Project ID

Use the Adobe add-font workflow to save the project ID from your Adobe Fonts web project.

### 2. Sync Detected Families

After validation, the plugin reads the project stylesheet and exposes the detected families in the shared library and selector workflow.

### 3. Use Adobe Families In Roles

Once synced, Adobe families can be:

- previewed in the Deploy Fonts workspace
- selected for draft roles
- applied sitewide like other sources

## Notes

- Adobe remains hosted remotely. The plugin does not download Adobe font files into local storage.
- Adobe families still participate in runtime stylesheet planning, Gutenberg presets, and Etch delivery where appropriate.
- If the project ID is invalid or inaccessible, the plugin will not expose the family list.

## Related Docs

- [Getting Started](../getting-started.md)
- [Font Library](../font-library.md)
- [Imports And Deliveries](../troubleshooting/imports-and-deliveries.md)
