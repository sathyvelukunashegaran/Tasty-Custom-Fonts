# Architecture

High-level orientation for how the plugin boots, stores data, and serves runtime typography.

## Use This Page When

- you are changing plugin behavior
- you need to find the right service layer
- you want the shortest path to understanding the runtime and admin architecture

## Key Structure

### Plugin Lifecycle

- `plugin.php` defines plugin constants, registers the autoloader, and boots `TastyFonts\Plugin` on `plugins_loaded`
- `Plugin` wires the service graph and registers runtime, admin, REST, and catalog hooks
- activation ensures upload storage exists and generated CSS can be written
- deactivation clears known transients and scheduled CSS regeneration hooks

### Major Service Layers

- `Repository/`: options, transients, library state, and activity logging
- `Support/`: storage, environment detection, and font utility helpers
- `Fonts/`: catalog, CSS building, runtime planning, local uploads, library mutations, and generated asset handling
- provider namespaces: Google, Bunny, and Adobe import/catalog logic
- `Admin/`: controller, page context building, view building, and section rendering
- `Api/`: REST adapter over admin actions
- `Updates/`: GitHub release updater integration

### Delivery Profile Model

Each family can store one or more delivery profiles. A profile carries:

- provider
- delivery type
- variants
- faces
- optional metadata

The active delivery profile controls runtime output for that family.

### Runtime Flow

- `CatalogService` builds the unified family catalog
- `RuntimeAssetPlanner` decides which local and remote assets should load
- `CssBuilder` generates runtime CSS for local deliveries and variable/class output
- `AssetService` manages generated CSS caching, writing, fallback delivery, and status reporting
- `RuntimeService` enqueues runtime assets for the frontend, block editor, admin preview paths, and Etch

## Notes

- The canonical generated stylesheet path is `uploads/fonts/.generated/tasty-fonts.css`.
- Google and Bunny can be self-hosted or CDN-based; Adobe remains hosted remotely.
- Block Editor Font Library sync is separate from the plugin’s own runtime output path.

## Related Docs

- [Testing](testing.md)
- [Release Process](release-process.md)
- [Translations](translations.md)
