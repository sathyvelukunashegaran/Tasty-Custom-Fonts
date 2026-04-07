# Font Library

Manage families, delivery profiles, publish state, fallback stacks, and per-family runtime overrides.

## Use This Page When

- you want to browse everything the plugin knows about
- you need to switch a family’s active delivery profile
- you need to change publish state, fallback behavior, or per-family `font-display`

## Steps

### 1. Filter The Library

Use the library filters to narrow by:

- source
- runtime state
- category
- search text

This is the fastest way to isolate imported families, local uploads, CDN profiles, or families that are currently in use.

### 2. Understand Publish State

Families can appear in three main runtime states:

- `In Use`: the family is currently used by an active live role
- `Published`: the family is available for runtime use
- `In Library Only`: the family stays stored in the library but is not served at runtime

### 3. Switch Active Delivery Profiles

A family can keep multiple delivery profiles side by side. The active delivery profile controls what the plugin serves.

Common examples:

- local self-hosted delivery for production
- Google CDN or Bunny CDN delivery for testing or fallback comparison
- multiple historical profiles kept for later reuse

### 4. Save Fallback Stacks

Each family can store its own fallback stack. This affects how generated stacks resolve when that family is used in runtime output and snippets.

### 5. Save Per-Family Font Display

Each family can also store a per-family `font-display` override. Use this when a specific family should behave differently from the global default set in `Settings`.

### 6. Delete Carefully

The library lets you delete:

- a whole family
- a delivery profile
- individual variants

The plugin blocks destructive actions when doing so would break a live applied role or an active profile still required by runtime output.

## Notes

- Google and Bunny self-hosted imports become local files under provider-specific upload directories.
- Adobe stays hosted remotely, but Adobe families still participate in selectors, previews, and live role assignments.
- The library is also where you confirm whether a family should stay `Published` or remain `In Library Only`.

## Related Docs

- [Getting Started](getting-started.md)
- [Local Fonts](providers/local-fonts.md)
- [Google Fonts](providers/google-fonts.md)
- [Bunny Fonts](providers/bunny-fonts.md)
- [Adobe Fonts](providers/adobe-fonts.md)
