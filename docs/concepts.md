# Concepts

Understand the three core ideas that underpin everything Tasty Custom Fonts does before diving into the task guides.

## Use This Page When

- you are new to the plugin and want a mental model before reading the task guides
- you want to understand why draft and live roles are separate
- you need to understand how the plugin generates and serves CSS
- you are choosing between font providers

---

## What This Plugin Actually Does

Tasty Custom Fonts is a typography management layer for WordPress. It lets you:

1. **Collect font families** from multiple sources (your own files, Google Fonts, Bunny Fonts, or Adobe Fonts) into a single managed library.
2. **Assign families to roles** — named slots like Heading, Body, and Monospace — that your theme or page builder can reference using CSS custom properties.
3. **Preview pairings** before publishing them live.
4. **Generate and serve CSS** that wires everything together on the frontend, in the block editor, and inside Etch.

You do not need to edit theme files, write `@font-face` rules by hand, or manage font file downloads manually. The plugin handles all of that.

---

## Delivery Profile Model

A **delivery profile** describes how a font family should be served at runtime. It carries:

- the provider (local, Google, Bunny, or Adobe)
- the delivery type (self-hosted or CDN/remote)
- the font variants and faces that belong to this delivery arrangement
- optional provider-specific metadata

A single family can hold **more than one delivery profile**. For example, you might keep a self-hosted profile and a Google CDN profile on the same family so you can switch between them without losing either configuration.

The **active delivery profile** is what the plugin uses at runtime. Switching the active profile immediately changes what gets served — no re-import needed.

If a family has no active delivery profile, it remains in the library but is not served.

### Why multiple profiles?

Think of delivery profiles as saved configurations, not font files. Keeping two profiles on one family means you can test CDN delivery for performance, then switch back to self-hosted if you want more control — without having to redo the import each time. Only the active profile affects runtime output.

---

## Draft/Live Role Model

The plugin separates **role assignment** from **live deployment**.

- A **draft role** is a working selection stored as a pending state. Saving a draft does not change live output.
- The **applied (live) roles** are what the plugin actually serves on the frontend, in Gutenberg, and in Etch.
- `Apply Sitewide` promotes the current draft roles to live output. Until that action is used, the live site is unaffected by draft changes.

This separation means you can freely experiment, preview, and compare multiple pairings before committing any change to the site.

### The three role slots

| Role | CSS variable | Always available? |
|---|---|---|
| Heading | `--font-heading` | Yes |
| Body | `--font-body` | Yes |
| Monospace | `--font-monospace` | Only when enabled in Settings → Behavior |

Each role slot can also be set to **fallback-only mode** (no family forced), which outputs the configured fallback stack without a family binding.

### Using role variables in your theme

Once roles are applied sitewide, the plugin emits something like:

```css
:root {
    --font-heading: 'Inter', sans-serif;
    --font-body: 'Source Serif 4', Georgia, serif;
}
```

You can reference these variables anywhere in your own CSS:

```css
h1, h2, h3 { font-family: var(--font-heading); }
p, li, td  { font-family: var(--font-body); }
```

Most themes and page builders that support CSS custom properties will pick these up automatically if you configure them to do so.

---

## Runtime CSS Pipeline

When the plugin needs to produce or refresh the generated stylesheet, these services run in sequence:

1. **`CatalogService`** — builds the unified family catalog from all provider sources and library state.
2. **`RuntimeAssetPlanner`** — decides which local and remote assets need to load based on the current live roles, active delivery profiles, and output settings.
3. **`CssBuilder`** — generates `@font-face` rules for self-hosted deliveries, plus role variables, optional family/category variables, weight tokens, and utility classes.
4. **`AssetService`** — writes the generated CSS to disk when file delivery is available, manages the cache state, and falls back to inline delivery if needed.
5. **`RuntimeService`** — enqueues the generated stylesheet for the frontend, Gutenberg, Etch, and admin preview paths; handles preloads and remote connection hints.

The canonical generated stylesheet is written to:

```
wp-content/uploads/fonts/.generated/tasty-fonts.css
```

If file delivery is disabled or unavailable, the plugin falls back to injecting CSS inline in the page `<head>`.

### What this means in practice

- The generated stylesheet contains all `@font-face` rules for self-hosted families and all role variable declarations.
- CDN and Adobe deliveries are loaded as separate `<link>` tags rather than being embedded in the generated stylesheet.
- If you open `Advanced Tools → Generated CSS`, you are looking at the exact output of this pipeline.

---

## Choosing a Provider

| Provider | Files downloaded? | API key needed? | Best for |
|---|---|---|---|
| Local files | Yes (you upload them) | No | Fonts you already own or licensed separately |
| Google Fonts | Optional (self-hosted) | Yes, for live search | Large open catalog, self-hosting for privacy |
| Bunny Fonts | Optional (self-hosted) | No | GDPR-friendly alternative to Google CDN |
| Adobe Fonts | No (Adobe-hosted) | No (project ID only) | Existing Adobe CC subscriptions with web projects |

Providers are not exclusive. You can mix sources — for example, use a self-hosted local upload for headings and a Bunny CDN delivery for body text.

### Decision guide

- **You already have font files** → use Local.
- **You want access to a large free catalog** → use Google or Bunny.
- **Your site handles EU user data and you want to avoid Google CDN** → use Bunny.
- **You have an Adobe CC subscription and want premium typefaces** → use Adobe.
- **You are not sure** → start with Bunny Fonts (no API key, GDPR-friendly, large catalog).

---

## Related Docs

- [Getting Started](getting-started.md)
- [Deploy Fonts](deploy-fonts.md)
- [Font Library](font-library.md)
- [Settings](settings.md)
- [Architecture](developer/architecture.md)
- [Glossary](glossary.md)
- [FAQ](faq.md)
