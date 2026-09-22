# Changelog

All notable changes to this theme are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

### Added

- The generated starter: the composition root, the nine providers, the render
  shell, the class-name resolver and the empty content model, with the quality
  gates that make an empty theme pass `composer check`.
- The render pipeline: `Surfaces` dispatch, `SurfacePlan`, `QueryContext`,
  `QueryKind` and `SiteProfile`, resolving each request to exactly one plan.
- Cacheability as a first-class declaration: every dispatch arm declares a
  `Cacheability` and a `FragmentScope`; an `Uncacheable` arm states its reason.
- `FragmentCache` with single-flight regeneration, `CachedFragment` and
  sha256 `FragmentKey`s derived from the site's own content graph.
- `HeaderPolicy` — the cache and validator headers a plan implies, merged into
  core's response headers at exactly one filter.
- The `Document` component: one shell per request, `wp_head` and `wp_footer`
  inside it, fed from the resolved plan.
- Reference gates: `docs/reference/surfaces.md` and `docs/reference/hooks.md`
  are generated from the live code, and a stale committed copy fails the suite.

### Changed

- `Bootstrap` renders the request through the dispatch table; `index.php`
  echoes `Bootstrap::render()`.
- Scaffold reconciliation: see docs/decisions/0001-scaffold-reconciliation.md.
