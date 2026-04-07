# Local Fonts

Upload local files or rescan the WordPress uploads directory for existing font files.

## Use This Page When

- you want to self-host fonts you already own or generated yourself
- you copied files into `wp-content/uploads/fonts/` outside the plugin UI
- you need to confirm supported formats and storage behavior

## Steps

### 1. Upload Files From The Dashboard

Use the local upload flow when you want the plugin to validate and store files for you.

Supported local formats:

- `WOFF2`
- `WOFF`
- `TTF`
- `OTF`

### 2. Rescan The Uploads Directory

Use rescan when files already exist somewhere under:

`wp-content/uploads/fonts/`

The scanner can discover supported local font formats stored in that tree.

### 3. Confirm The Family In The Library

After upload or rescan:

- confirm the family appears in the `Font Library`
- review its detected variants
- set a fallback stack if needed
- decide whether it should stay `Published` or `In Library Only`

## Notes

- Local uploads feed the same library used by Google, Bunny, and Adobe sources.
- Local families are self-hosted by nature; they do not depend on remote stylesheet delivery.
- Generated runtime CSS will only serve a family once the family is runtime-visible and used by the applicable output path.

## Related Docs

- [Getting Started](../getting-started.md)
- [Font Library](../font-library.md)
- [Generated CSS](../troubleshooting/generated-css.md)
