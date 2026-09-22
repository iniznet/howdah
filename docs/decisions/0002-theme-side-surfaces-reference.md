# ADR 0002 — A theme-side surfaces reference gate

Date: 2026-09-22 · Status: accepted

## Context

The dispatch table is the theme's most safety-critical structure: every arm
declares what it costs to serve. Prose can promise that every arm declares its
cacheability, but a promise that lives only in a markdown file does not exist.
The theme needs a gate that fails when an arm ships without a declaration —
and a human-readable reference that cannot silently drift from the code.

The packages already ship a hook-reference generator; a surfaces equivalent
belongs to the theme, because only the theme knows its dispatch table.

## Decision

`tests/Support/SurfacesReference.php` is the theme-side gate. It parses the
`match (true)` table in `app/Render/Surfaces.php`, and:

- **dispatch-audit** — every arm must name its Surface class, declare a
  `Cacheability` and a `FragmentScope`, and — if uncacheable — state a reason
  in its own source. The parser reads the declaration, not a second registry.
- **reference generation** — `MAHOUT_SURFACES_REGENERATE=1` rewrites
  `docs/reference/surfaces.md`, one row per arm.
- **staleness gate** — the committed reference must be byte-identical to the
  generated document, or the suite fails with the regeneration command.

The parser is shape-tolerant — a condition may sit on its own line or share
the arrow's line — because formatting is not the contract; the declaration is.

## Consequences

Adding a dispatch arm without its declarations fails three ways at once: the
unit audit, the staleness gate, and a reference the generator refuses to
produce. Removing a declaration from an existing arm fails the same gates.
The reference document is regenerated with the change that made it stale.
