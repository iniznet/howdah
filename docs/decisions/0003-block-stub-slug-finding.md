# ADR 0003 — The block-mode stub ships an undeclared slug

Date: 2026-09-22 · Status: accepted

## Context

The mahout-scaffold ships a block-mode template stub:
`stubs/modes/block/templates/index.html`. This theme's rule is that every
block template must be declared in `config/block-templates.php` with its
reason, and `index` sits in the never column — it is the Surfaces entry
point, and shipping it would replace the dispatch table with a fixed
template.

## Finding

Running the theme's slug gate over the scaffold's block-mode tree, exactly as
shipped, produces one offence:

```
templates/index.html is shipped but not declared with a reason.
```

The same tree corrected to an editorial slug with a declared reason produces
zero offences. The gate accepts the stub's file contents; it refuses the
slug, and `index` could never be declared legitimately.

## Decision

The block stub is not adopted. A block-mode theme composes from an editorial
declaration: a `templates/*.html` slug chosen for the site's editorial
structure, declared with its reason. The stub is recorded here so the
finding is durable and the scaffold can adopt the same rule upstream.

## Consequences

- `tests/Support/BlockSlugPolicy.php` runs in the unit suite; a
  `templates/*.html` file without a declaration fails the build.
- `front-page` and `home` remain conditional on the project declaring the
  front page editorial; the never column is fixed.
- The scaffold should regenerate its block-mode stub under the same rule.
