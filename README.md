# howdah

A WordPress theme built as a modular monolith: four layers, one process, no
hidden state, a render pipeline whose every arm declares what it costs to
serve.

The theme is correct and complete with no object cache, no page cache and no
CDN — and gets faster as each is added, with no configuration change and no
code change.

## Requirements

- PHP 8.4+
- WordPress 7.1+
- Node.js 22+ and npm (build only — the theme runs without a built asset
  bundle when none is present)
- Composer, for the development toolchain

## Installation

The theme is a drop-in:

1. Copy this directory into `wp-content/themes/howdah`.
2. Activate **howdah** in Appearance → Themes.
3. Optional, for editors: build the front-end bundle (`npm ci && npm run build`)
   or run it from the Vite dev server.

There is no setup step, no activation hook and no required plugin. Options the
theme registers are non-autoloaded; the theme creates no database tables of its
own.

## Development

```bash
composer install          # dev toolchain (PHPStan max, Psalm, Rector, PHPUnit)
npm ci                    # Vite
npm run build             # build/manifest.json
npm run dev               # watch mode

composer test             # PHPUnit
composer check            # format, stan, psalm, arch, rector, tests, references
```

`composer check` must pass before every commit. It is not advisory: the
working tree fails loudly rather than shipping a stale reference document or
an undeclared cache surface.

## Architecture

The theme is a modular monolith (see docs/architecture.md for the layer
table and the full rules). The composition root is `app/Bootstrap.php`: it
boots the provider chain and renders the request. `Surfaces` — the dispatch
table in `app/Render/Surfaces.php` — resolves the request to exactly one
Surface, each arm declaring its cacheability and its fragment scope; a Surface
that cannot be cached states its reason. The `Document` component emits one
shell per request; fragments are stored, keyed by the site's own content
graph, and invalidated through the theme's purge seam.

Start here:

- [docs/architecture.md](docs/architecture.md) — layers, dependency rules, the render pipeline
- [docs/contracts.md](docs/contracts.md) — what the theme promises other code
- [docs/extending.md](docs/extending.md) — adding a feature, a dispatch arm or a hook
- [docs/getting-started.md](docs/getting-started.md) — commands, reference regeneration, the probe suite
- [docs/decisions/](docs/decisions/) — why the tree looks the way it does

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request, and
route your change by kind, not by file path. Security issues: see
[SECURITY.md](SECURITY.md) — please do not open a public issue. Behaviour:
[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
