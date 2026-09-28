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
  core's response headers at exactly one filter; `mahout-render`'s
  `Cache\HeaderPolicy` since the query-mechanics move below.
- The `Document` component: one shell per request, `wp_head` and `wp_footer`
  inside it, fed from the resolved plan.
- Reference gates: `docs/reference/surfaces.md`, `docs/reference/actions.md` and
  `docs/reference/filters.md` are generated from the live code, and a stale committed copy
  fails the suite.

### Changed

- The generated hook reference is now two documents — `docs/reference/actions.md` and
  `docs/reference/filters.md`, replacing `docs/reference/hooks.md`. A single mixed table
  asked the reader to filter rows for the question they actually came with, which hooks
  fire and forget versus which hooks return a value, and that distinction is already
  recorded on every constant's docblock. `composer hooks:check` gates both files, and a
  package that declares none of one kind still carries the other document, so the gate
  cannot quietly stop running. Adopted from `iniznet/mahout-devtools` 2.0.1, whose
  `hooks:check` and `hooks:generate` take `--outdir=docs/reference`; the canonical command
  text lives in that package's gate manifest, and this repository's scripts are compared
  against it by `composer config:check`.
- `docs/contracts.md` and `docs/getting-started.md` name three generated documents now —
  the surfaces reference and the two hook documents — and `docs/planning/` is untouched
  because it is the private corpus, published with no repository.

- `Bootstrap` renders the request through the dispatch table; `index.php`
  echoes `Bootstrap::render()`.
- The query mechanics moved into the packages that own them, and the theme kept
  the decisions. The indexed search path — the tokeniser, the `MATCH` clause,
  the two `posts_search` filters and the once-per-request report of their
  absence — is `mahout-db`'s: the repository states a search intent and takes
  the query args from the swap, so it declares no search grammar. The response
  headers — the derivation, the narrowing and merge of core's map, and the
  conditional `304` — are `mahout-render`'s: the theme records only the
  reduction it owes the operator. The dispatch table is written with
  `SurfacePlanBuilder`, whose terminals are the only way to reach a plan, and
  the surfaces reference reports one row per plan an arm reaches.
- The field layer's admin screens are derived, not hand-attached.
  `mahout-fields`' `Admin\FieldsUiProvider` turns the theme's `Contracts\Panels`
  binding into the metaboxes, the save entry, the route reads and the
  write-failure notice, and the theme's `Contracts\OptionScreens` binding into
  the settings pages, their save entries and their notices — attaching none of
  them for a theme that declares neither. `AdminProvider` names only the admin
  surface the theme owns: the status screen, the migration notices and the
  list columns. The notice's string moved to the `mahout-fields` text domain,
  so the theme's POT lost that entry.
- The theme's hand-rolled display options are gone — the screen, its nonce
  and sanitising, the `howdah_` option keys and their exception — because
  `mahout-fields`' option screen is the one settings surface:
  config/display-options.php declares `OptionScreen` values, the editor seam
  binds them under the package's `Contracts\OptionScreens`, and a declared
  screen's values reach `wp_options` under the package's own key. The starter
  declares none, and `Capabilities::EditThemeOptions` went with the screen it
  gated.
- The theme's own copies of `SearchTerms`, `MatchClause`, `FieldPanel`,
  `FieldWriteFailedNotice` and `Support\Cache\HeaderPolicy` are gone, with the
  four hook constants and the three named exception constructors that existed
  only to serve them.
- Two gates hold the move in place: a search must deliver the package clause to
  the database — parity alone cannot tell a swapped fragment from core's `LIKE`
  — and no theme file may register a metabox, bind a field to a REST route or
  name a core search filter.
- Scaffold reconciliation: see docs/decisions/0001-scaffold-reconciliation.md.
