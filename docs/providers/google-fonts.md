# Google Fonts

Import Google Fonts as self-hosted files or keep them on the Google CDN.

## Use This Page When

- you want to import a Google family into the plugin library
- you need to decide between self-hosted and CDN delivery
- you are troubleshooting Google API key or catalog access issues

## Steps

### 1. Save A Google API Key

Live Google search requires a valid Google Fonts API key saved in the plugin settings path used for Google access.

If search is unavailable, open the Google key settings from the add-font workflow and validate the key first.

### 2. Search For A Family

Use the Google add-font flow to search for the family you want and review available variants.

### 3. Choose A Delivery Mode

Google imports support two delivery models:

- `Self-hosted`: download supported files into the WordPress uploads directory
- `CDN`: keep runtime delivery on Google’s stylesheet infrastructure

### 4. Import And Review

After import:

- confirm the family appears in the `Font Library`
- review its delivery profile
- choose whether it should be runtime-visible
- assign it to a role when ready

## Notes

- Self-hosted Google imports are stored under `wp-content/uploads/fonts/google/<family-slug>/`.
- CDN deliveries remain remote at runtime, but the family still participates in previews, selectors, and live role assignments.
- The plugin uses modern remote CSS handling and normalizes imported faces into its delivery profile model.

## Related Docs

- [Font Library](../font-library.md)
- [Settings](../settings.md)
- [Imports And Deliveries](../troubleshooting/imports-and-deliveries.md)
