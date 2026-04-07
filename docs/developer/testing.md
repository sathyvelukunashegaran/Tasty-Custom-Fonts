# Testing

Run and extend the local test suite for the plugin.

## Use This Page When

- you are changing plugin behavior
- you need the standard local verification commands
- you want to add PHP or JavaScript coverage

## Steps

### 1. Run The PHP Test Suite

```bash
php tests/run.php
```

This is the main local PHP harness. It does not require PHPUnit, Composer, or external services.

### 2. Run The JavaScript Contract Tests

```bash
node --test tests/js/*.test.cjs
```

These tests cover shared browser/Node-compatible admin and canvas helper contracts.

### 3. Run The PHP Syntax Sweep

```bash
find . -name '*.php' -not -path './output/*' -print0 | xargs -0 -n1 php -l
```

## Adding Tests

### PHP

- add closures to the appropriate `tests/cases/*.php` file
- use the local test harness helpers from `tests/bootstrap.php` and `tests/support/wordpress-harness.php`
- call `resetTestState()` when the test touches global or option-like state

### JavaScript

- add tests under `tests/js/*.test.cjs`
- use Node’s built-in `node:test` and `node:assert`

## Notes

- There is no Composer install step and no npm install step for the current repo workflow.
- The release workflow runs the PHP syntax sweep, the PHP suite, and the JS contract tests before building a release.

## Related Docs

- [Architecture](architecture.md)
- [Release Process](release-process.md)
