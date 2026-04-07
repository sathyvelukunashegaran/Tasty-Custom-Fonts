# Bunny Fonts

Import Bunny Fonts as self-hosted files or keep them on the Bunny CDN.

## Use This Page When

- you want Bunny-hosted families in the plugin library
- you need to choose between self-hosted and CDN delivery
- you are troubleshooting Bunny import behavior

## Steps

### 1. Search Or Select A Family

Use the Bunny add-font workflow to search the Bunny catalog and inspect the family details the plugin exposes.

### 2. Choose A Delivery Mode

Bunny imports support:

- `Self-hosted`: download the imported files into the WordPress uploads directory
- `CDN`: keep runtime delivery on Bunny’s stylesheet infrastructure

### 3. Import And Review

After import:

- confirm the family appears in the `Font Library`
- review its variants and active delivery profile
- decide whether it should stay `Published` or `In Library Only`
- assign it to draft roles when ready

## Notes

- Self-hosted Bunny imports are stored under `wp-content/uploads/fonts/bunny/<family-slug>/`.
- CDN deliveries stay remote at runtime but still participate in previews and role selection.
- The plugin validates Bunny download URLs before writing self-hosted files.

## Related Docs

- [Font Library](../font-library.md)
- [Generated CSS](../troubleshooting/generated-css.md)
- [Imports And Deliveries](../troubleshooting/imports-and-deliveries.md)
