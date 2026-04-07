# Release Process

Create, verify, tag, and publish a plugin release.

## Use This Page When

- you are cutting a new stable version
- you need the expected changelog and tag flow
- you want to understand how GitHub releases and WordPress updates fit together

## Steps

### 1. Start From A Clean `main`

The release process assumes:

- you are on `main`
- the worktree is clean
- `CHANGELOG.md` has meaningful `Unreleased` notes ready to publish

### 2. Run The Release Helper

```bash
bin/release <version>
```

Example:

```bash
bin/release 1.6.1
```

This helper:

- updates version markers in `plugin.php`
- promotes `Unreleased` notes into a dated changelog section
- runs the PHP syntax sweep
- runs `php tests/run.php`
- runs `node --test tests/js/*.test.cjs`
- creates the release commit
- creates the annotated tag
- pushes `main` and the tag unless `--no-push` is used

### 3. Let GitHub Actions Publish The Release

Pushing the semver tag triggers `.github/workflows/release.yml`, which:

- validates the tag against the plugin version
- rebuilds the verification checks
- archives the plugin into a release ZIP
- generates release notes from the changelog section
- publishes or updates the GitHub release

## GitHub Updater Implications

- The plugin advertises the GitHub repository as its `Update URI`
- the bundled updater exposes attached GitHub release ZIPs as WordPress plugin updates
- release asset naming matters because the updater expects `tasty-fonts-<version>.zip`
- the packaged plugin directory remains `etch-fonts/` for update continuity

## Notes

- If user-facing behavior changes, update the docs alongside the changelog before tagging.
- The release workflow is the source of the installable GitHub release ZIP used by the updater.

## Related Docs

- [Testing](testing.md)
- [Translations](translations.md)
- [Changelog](../../CHANGELOG.md)
