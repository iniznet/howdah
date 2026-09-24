# Getting started

**Read this page in this order:** this page (prerequisites, commands, the
reference regeneration you will trigger) → [tutorial.md](./tutorial.md) for a
worked feature → [AGENTS.md](../AGENTS.md), the complete contract →
[architecture.md](./architecture.md) and [extending.md](./extending.md). The
tutorial is the fast lane; AGENTS.md is the one every commit is held to.

## Prerequisites

- PHP 8.4+ with the usual WordPress stack
- WordPress 7.1+
- Node.js 22+ and npm (build only)
- Composer

## Install and run

```bash
composer install    # dev toolchain
npm ci              # Vite and the front-end toolchain
npm run build       # emits build/manifest.json
npm run dev         # watch mode
```

The theme runs without a build step: `AssetsProvider` reads `build/manifest.json` when it exists and renders nothing when it does not. The build is for development; it is never required for the theme to be correct.

## Commands

| Command | What it does |
|---|---|
| `composer test` | PHPUnit (unit + integration) |
| `composer format` | PHP-CS-Fixer (`@PSR12` + `@Symfony`) |
| `composer stan` | PHPStan at max level, no baseline |
| `composer psalm` | Psalm taint analysis |
| `composer arch` | architecture rules |
| `composer rector` | Rector dry-run |
| `composer hooks:check` | the hook reference is current |
| `composer i18n:check` | the generated POT is current |
| `composer doctor` | the installation assembles |
| `composer check` | all of the above |

`composer check` must pass before every commit. No exceptions.

## Regenerating the reference documents

Two documents are generated from the live tree; a stale committed copy fails the suite.

```bash
# the surfaces reference, from the dispatch table
MAHOUT_SURFACES_REGENERATE=1 vendor/bin/phpunit --filter SurfacesReferenceTest

# the hook reference
composer hooks:generate
```

Review the diff, then commit the regenerated document with the change that made it stale.

## Test modes

The suite runs three ways, and all three must pass:

- **Default** — unit and integration tests, no WordPress.
- **Layer 0** — every front-end test passing with no object cache, no page cache and no CDN. The suite never depends on a caching layer existing.
- **Byte-identical** — two anonymous visitors on a `Shared` Surface produce identical output, asserted directly.

The block-template slug probe (`tests/Support/BlockSlugPolicy.php`) runs as part of the unit suite and refuses any `templates/*.html` slug that is not declared with its reason in `config/block-templates.php`.
